<?php

namespace App\Services\Leads;

use Carbon\Carbon;
use App\Models\Leads\Pub;
use App\Models\LeadsClone;
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
        $lead = [];
        $response = [];
        $lead_form = [];
        $result = [];

        $callsDataRepeat = $forms->mapWithKeys(function ($leads) use ($period, $auth_token, &$lead_form, $provider) {
            $do = 1;
            $per_page = 100;
            $query_parameters = [
                'access_token' => $auth_token,
                'fields' => 'campaign_name,field_data,form_id,created_time',
                'pretty' => 0,
                'limit' => $per_page,
            ];
            $created_at_from = $period->from();
            while ($do > 0) {
                $res = Http::get(self::FB_URL . '/' . $leads . '/leads', $query_parameters)->json();
                $response = new Collection($res['data']);
                $response = $response->filter(function ($leads) use ($created_at_from) {
                    $date_leads = Carbon::create($leads['created_time']);

                    return $date_leads->greaterThanOrEqualTo($created_at_from);
                })->map(function ($call) use ($provider) {
                    $result = [];
                    $date_leads = Carbon::create($call['created_time']);
                    $date = $date_leads->setTimezone('America/New_York')->format('Y-m-d H:i:s');
                    foreach ($call['field_data'] as $value) {
                        $result[$value['name']] = $value['values'][0];
                    }
                    $fullName = $result['full_name'];
                    $names = preg_split('/\s+/', $fullName);
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
                    }
                    $result['firstName'] = trim($first . ' ' . $middle);
                    $result['lastName'] = trim($mlast . ' ' . $last);
                    $result['phone'] = trim($result['phone_number']);
                    $result['campaign_name'] = $call['campaign_name'] ?? '';
                    $result['sub_ID'] = $call['form_id'];
                    $result['pub_id'] = 106;
                    $result['type'] = 'legal';
                    $this->validated_service->validatePhone($result);
                    $this->validated_service->validatePubWithoutUser($result, $provider);
                    $this->validated_service->validateSub($result);
                    $this->validated_service->validateMetrics($result);
                    $result['pub_ID'] = $result['pub_id'];

                    $insert = $this->lead_api_repository->resource($result);
                    if ($this->lead_api_repository->create($insert)->wasRecentlyCreated) {
                        $this->dispatch($insert);
                    }

                    return $result;
                });

                $do = $response->count();
                if ($do > 0) {
                    $lead_form = array_merge($response->toArray(), $lead_form);
                    $query_parameters['after'] = $res['paging']['cursors']['after'];
                }
            }

            return $lead_form;
        });

        return $callsDataRepeat->count();
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

    public function superImport(Period $date)
    {
        $key = hash('sha256', $date->from()->format('Y-m-d') . $date->to()->endOfMonth()->format('Y-m-d'));
        $data = LeadsClone::orderBy('date_history', 'asc')->get();

        $data->map(function ($lead) {
            $result = [];

            $result['firstName'] = $lead->firstName;
            $result['lastName'] = $lead->lastName;
            $result['phone'] = $lead->phone;
            $result['campaign_name'] = $lead->campaign_name;
            $result['sub_ID'] = $lead->sub_ID;
            $result['pub_id'] = $lead->pub_ID;
            $result['type'] = $lead->offers;
            $result['created_at'] = $lead->created_at;
            $result['date_history'] = $lead->date_history;
            $result['updated_at'] = $lead->updated_at;
            $result['email'] = $lead->email;
            $result['state'] = $lead->state;
            $result['zip_code'] = $lead->zip_code;
            $result['ip'] = $lead->ip;
            $result['data'] = json_decode($lead->data);
            $result['universal_leadid'] = $lead->universal_leadid;
            $result['xxTrustedFormToken'] = $lead->xxTrustedFormToken;
            $this->validated_service->validatePhone($result);
            $this->validated_service->validatePubWithoutUser($result, 1);
            $this->validated_service->validateSub($result);
            $this->validated_service->validateMetrics($result);
            $result['pub_ID'] = $result['pub_id'];
            unset($result['pub_id']);

            if (!empty($result['phone'])) {
                $insert = $this->lead_api_repository->resource($result);
                if ($this->lead_api_repository->create($insert)->wasRecentlyCreated) {
                    $log['status'] = !empty($lead->lead_id) ? 'success' : 'error';
                    $log['phone'] = $insert['phone'];
                    $log['lead_id'] = $lead->lead_id;
                    $message = $lead->booberdo_log ?? '';
                    $this->log_repository->logginProvider($message, $log);
                    if (in_array($insert['type'], ['legal', 'debt', 'tax_debt'])) {
                        $message = $lead->goodcall_log ?? '';
                        $log['status'] = (!empty($lead->goodcall_lead_id) && $lead->goodcall_lead_id > 0) ? 'success' : 'error';
                        $log['phone'] = $insert['phone'];
                        $log['lead_id'] = $lead->goodcall_lead_id;
                        $this->log_repository->logginPhoneRoom($message, $log, 1);
                    }
                }
            }
        });

        return $data->count();
    }

    public function isInvalidName($value): bool
    {
        return is_null($value)
            || trim($value) === ''
            || strtolower(trim($value)) === 'null'
            || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $value);
    }
}
