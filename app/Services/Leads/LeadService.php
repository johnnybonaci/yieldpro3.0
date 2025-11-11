<?php

namespace App\Services\Leads;

use Carbon\Carbon;
use App\Models\Leads\Pub;
use App\ValueObjects\Period;
use App\Models\Leads\Provider;
use App\Models\Leads\PhoneRoom;
use Illuminate\Support\Collection;
use App\Repositories\LogRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use App\Repositories\Leads\LeadApiRepository;
use App\Interfaces\Leads\PostingServiceInterface;

class LeadService extends ImportService implements PostingServiceInterface
{
    public const FB_URL = 'https://graph.facebook.com/v16.0';

    public Collection $providers;

    public Collection $phone_rooms;

    public function __construct(
        private LogRepository $log_repository,
        private LeadApiRepository $lead_api_repository,
        private ValidatedService $validated_service,
    ) {
    }

    public function submit(array $data): bool
    {
        $log['phone'] = $data['caller_id'];

        try {
            $response = Http::asJson()->baseUrl(self::FB_URL)->withHeaders([
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ])->post('leads', $data)->throw()->collect('lead');

            $log['status'] = 'success';
            $log['lead_id'] = $response['id'] ?? '';
            $this->log_repository->logginProvider(json_encode([]), $log);
            $response = true;
        } catch (RequestException $e) {
            $log['status'] = 'error';
            $this->log_repository->logginProvider($e->response->body(), $log);
            $response = false;
        }

        return $response;
    }

    /**
     * Import Leads from Facebook.
     */
    public function import(Model $models, int $provider, Period $period): int
    {
        $auth_token = env('FACEBOOK_USER_TOKEN');
        $forms = new Collection(['869138990831414', '6217642471590057', '153782883856490']);
        $lead_form = [];

        $callsDataRepeat = $forms->mapWithKeys(function ($formId) use ($period, $auth_token, &$lead_form, $provider) {
            $leads = $this->fetchLeadsFromForm($formId, $period, $auth_token, $provider);
            $lead_form = array_merge($leads, $lead_form);

            return $leads;
        });

        return $callsDataRepeat->count();
    }

    /**
     * Fetch leads from a specific form.
     */
    private function fetchLeadsFromForm(string $formId, Period $period, string $auth_token, int $provider): array
    {
        $lead_form = [];
        $query_parameters = $this->buildQueryParameters($auth_token);
        $created_at_from = $period->from();

        do {
            $res = Http::get(self::FB_URL . '/' . $formId . '/leads', $query_parameters)->json();
            $response = new Collection($res['data']);

            $filteredLeads = $this->filterAndProcessLeads($response, $created_at_from, $provider);

            $leadsCount = $filteredLeads->count();
            if ($leadsCount > 0) {
                $lead_form = array_merge($filteredLeads->toArray(), $lead_form);
                $query_parameters['after'] = $res['paging']['cursors']['after'];
            }
        } while ($leadsCount > 0);

        return $lead_form;
    }

    /**
     * Build query parameters for Facebook API.
     */
    private function buildQueryParameters(string $auth_token): array
    {
        return [
            'access_token' => $auth_token,
            'fields' => 'campaign_name,field_data,form_id,created_time',
            'pretty' => 0,
            'limit' => 100,
        ];
    }

    /**
     * Filter and process leads.
     * @param mixed $created_at_from
     */
    private function filterAndProcessLeads(Collection $response, $created_at_from, int $provider): Collection
    {
        return $response
            ->filter(fn ($lead) => $this->isLeadValid($lead, $created_at_from))
            ->map(fn ($call) => $this->processLead($call, $provider));
    }

    /**
     * Check if lead is valid based on creation date.
     * @param mixed $created_at_from
     */
    private function isLeadValid(array $lead, $created_at_from): bool
    {
        $date_leads = Carbon::create($lead['created_time']);

        return $date_leads->greaterThanOrEqualTo($created_at_from);
    }

    /**
     * Process a single lead.
     */
    private function processLead(array $call, int $provider): array
    {
        $result = $this->extractFieldData($call);
        $this->parseFullName($result);
        $this->addLeadMetadata($result, $call);
        $this->validateAndEnrichLead($result, $provider);
        $this->saveAndDispatchLead($result);

        return $result;
    }

    /**
     * Extract field data from call.
     */
    private function extractFieldData(array $call): array
    {
        $result = [];
        foreach ($call['field_data'] as $value) {
            $result[$value['name']] = $value['values'][0];
        }

        return $result;
    }

    /**
     * Parse full name into components.
     */
    private function parseFullName(array &$result): void
    {
        $fullName = $result['full_name'];
        $names = preg_split('/\s+/', $fullName);

        list($first, $middle, $mlast, $last) = $this->splitNames($names);

        $result['firstName'] = trim($first . ' ' . $middle);
        $result['lastName'] = trim($mlast . ' ' . $last);
    }

    /**
     * Split names array into components.
     */
    private function splitNames(array $names): array
    {
        $first = $middle = $mlast = $last = '';

        switch (count($names)) {
            case 4:
                list($first, $middle, $mlast, $last) = $names;

                break;
            case 3:
                list($first, $middle, $last) = $names;

                break;
            case 2:
                list($first, $last) = $names;

                break;
            case 1:
                list($first) = $names;

                break;
            default:
                $first = $names[0] ?? '';
                $middle = $names[1] ?? '';
                $mlast = $names[2] ?? '';
                $last = $names[3] ?? '';

                break;
        }

        return [$first, $middle, $mlast, $last];
    }

    /**
     * Add lead metadata.
     */
    private function addLeadMetadata(array &$result, array $call): void
    {
        $result['phone'] = trim($result['phone_number']);
        $result['campaign_name'] = $call['campaign_name'] ?? '';
        $result['sub_ID'] = $call['form_id'];
        $result['pub_id'] = 106;
        $result['type'] = 'legal';
    }

    /**
     * Validate and enrich lead data.
     */
    private function validateAndEnrichLead(array &$result, int $provider): void
    {
        $this->validated_service->validatePhone($result);
        $this->validated_service->validatePubWithoutUser($result, $provider);
        $this->validated_service->validateSub($result);
        $this->validated_service->validateMetrics($result);
        $result['pub_ID'] = $result['pub_id'];
    }

    /**
     * Save lead and dispatch if newly created.
     */
    private function saveAndDispatchLead(array $result): void
    {
        $insert = $this->lead_api_repository->resource($result);
        if ($this->lead_api_repository->create($insert)->wasRecentlyCreated) {
            $this->dispatch($insert);
        }
    }

    /**
     * dispatch job to send lead to provider.
     */
    public function dispatch(Collection $data, bool $job = false): void
    {
        $this->providers = $this->lead_api_repository->getActiveAll(new Provider());
        $this->phone_rooms = $this->lead_api_repository->getActiveAll(new PhoneRoom());

        $pub = $this->lead_api_repository->find($data['pub_id'], new Pub());
        // Logic to Send Provider
        $this->providers->each(function ($provider) use ($data, $pub, $job) {
            if ($this->lead_api_repository->checkPostingLead($pub, $provider)) {
                $data['pubs'] = $pub->get('setup');
                $this->lead_api_repository->process($data, __toJob($provider, $job));
                if ($data['pub_id'] == 196) {
                    $this->sendMc($data, $provider, $job);
                }
            }
        });
        // End to logic send Provider
        $pub = $this->lead_api_repository->find($data['pub_id'], new Pub());
        // Logic to Send PhoneRoom
        $this->phone_rooms->each(function ($phone_room) use ($data, $pub, $job) {
            if ($this->lead_api_repository->checkPostingLead($pub, $phone_room)) {
                $data['pubs'] = $pub->get('setup');
                $this->lead_api_repository->process($data, __toJob($phone_room, $job));
            }
        });
        // End to logic send Provider
    }

    public function sendMc($data, $provider, $job = false)
    {
        $data['pub_id'] = 198;
        $data['type'] = 'MC';
        $data['sub_id4'] = 'MC';
        $data['campaign_name_id'] = 'Medicare Calls';
        $data['sub_id3'] = 'Medicare Calls';

        $this->lead_api_repository->process($data, __toJob($provider, $job));
    }

    public function isInvalidName($value): bool
    {
        return is_null($value)
            || trim($value) === ''
            || strtolower(trim($value)) === 'null'
            || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $value);
    }
}
