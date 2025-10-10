<?php

namespace App\Services\Leads;

use Carbon\Carbon;
use App\Models\Leads\Lead;
use App\Models\Leads\Buyer;
use Illuminate\Support\Str;
use App\ValueObjects\Period;
use App\Models\Leads\Provider;
use App\Models\Leads\Recording;
use App\Models\Leads\Convertion;
use Illuminate\Support\Collection;
use App\Enums\TranscriptStatusEnum;
use App\Repositories\LogRepository;
use Illuminate\Support\Facades\Log;
use App\Jobs\Leads\TranscriptionJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use App\Repositories\Leads\TrackDriveRepository;
use App\Interfaces\Leads\PostingServiceInterface;

class TrackDriveService extends ImportService implements PostingServiceInterface
{
    private $collects = 'calls';

    public function __construct(
        private LogRepository $log_repository,
        private TrackDriveRepository $track_drive_repository,
        private ValidatedService $validated_service,
    ) {
    }

    /**
     * Send data to Service Track Drive.
     */
    public function create(array $data, Model $provider): void
    {
        if (!empty($data)) {
            $data['provider'] = $provider;
            if ($this->submit($data)) {
                $post = $this->track_drive_repository->postback($data);
                if ($post) {
                    $this->postBack($post);
                }
            }
        }
    }

    /**
     * Posting Lead to TrackDrive.
     */
    public function submit(array $data): bool
    {
        $log['phone'] = $data['caller_id'];
        $log['data'] = $data;
        $provider = $data['provider'];
        $log['provider_id'] = $provider->id;
        unset($data['provider']);

        try {
            $response = Http::asJson()->baseUrl($provider->url)->withHeaders([
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
     * Summary of postback.
     */
    public function postback(array $data): void
    {
        try {
            Http::get('https://www.chant3rm1.com/lead_nc.asp', $data)->throw();
            Log::info('PostBack: 119 ', ['success']);
        } catch (RequestException $e) {
            Log::info('PostBack: 119 ', [$e->response->body()]);
        }
    }

    /**
     * Import data From TrackDrive.
     */
    public function import(Model $models, int $provider, Period $period, bool $command = true): int
    {
        $this->provider = Provider::find($provider);
        $this->auth_token = __toHashValidated(__toEnviroment($this->provider->service, 'AUTH_TOKEN', $provider), $this->provider->api_key);
        $date = [
            'created_at_from' => $period->from()->format('Y-m-d H:i:s O'),
            'created_at_to' => $period->to()->format('Y-m-d H:i:s O'),
        ];
        $accumulative = new Collection();

        $query_parameters = array_filter(self::make($this->columns, $this->per_page, $this->page));
        $query_parameters = array_merge($query_parameters, $date);
        do {
            $response = Http::withHeaders(['Authorization' => 'Basic ' . $this->auth_token])
                ->get($this->provider->url . '/' . $this->collects, $query_parameters)->collect($this->collects);
            $accumulative = $accumulative->merge($response);
            $this->do = $response->count();
            $query_parameters['page'] = ++$this->page;
        } while ($this->do);

        $calls = $accumulative;

        return $this->process($calls, $provider, $command);
    }

    /**
     * Process Conversions Calls.
     */
    public function process(Collection $data, int $provider, bool $command = true): int
    {
        $response = $data->map(function ($lead) use ($provider, $command) {
            if (empty($lead['caller_number'])) {
                $phone_ramdom = (string) random_int(1000000000, 9999999999);
                $lead['caller_number'] = '+1' . $phone_ramdom;
            }
            $lead_verified = $this->validate($lead, $provider);

            if ($lead_verified->phone) {
                $this->convertions($lead, $lead_verified, $provider, $command);
            }
        });

        return $response->count();
    }

    /**
     * Validates TrackDrive Fields.
     */
    public function validate(array $data, int $provider): Lead
    {
        $lead_verified = ['phone' => $data['caller_number']];
        $lead_verified['pub_id'] = !empty($data['token-s1']) ? $data['token-s1'] : 1;
        $lead_verified['type'] = $data['offer'];
        $lead_verified['sub_ID'] = !empty($data['token-sub_id']) ? $data['token-sub_id'] : 0;
        $lead_verified['campaign_name'] = $data['offer'];
        $this->validated_service->validatePhone($lead_verified);
        if (empty($lead_verified['phone'])) {
            return new Lead();
        }
        $lead = $this->track_drive_repository->findByPhone($lead_verified['phone']);
        if (!$lead) {
            $this->validated_service->validatePubWithoutUser($lead_verified, $provider);
            $this->validated_service->validateFields($lead_verified, $data);
            $this->validated_service->validateSub($lead_verified);
            $this->validated_service->validateMetrics($lead_verified);
            $lead_verified['sub_id'] = $lead_verified['sub_ID'];
            $lead_verified['campaign_name_id'] = $lead_verified['campaign_name'];
            unset($lead_verified['sub_ID']);
            unset($lead_verified['campaign_name']);
            $lead_verified['created_at'] = Carbon::create($data['created_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d H:i:s');
            $lead_verified['date_history'] = Carbon::create($data['created_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d');
            $lead_verified['updated_at'] = Carbon::create($data['updated_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d H:i:s');
            $lead = $this->track_drive_repository->create(collect($lead_verified));
        } else {
            $lead->update(['state' => !empty($data['token-state']) ? $data['token-state'] : $data['caller_city']]);
        }

        return $lead;
    }

    /**
     *  Set && save Convertions.
     */
    public function convertions(array $lead, Lead $lead_verified, int $provider, bool $command = true): bool
    {
        static $enabledBuyerIds = null;

        if ($enabledBuyerIds === null) {
            $enabledBuyerIds = Buyer::where('enable_transcriptions', true)
                ->pluck('id')
                ->toArray();
        }

        $status['status'] = [];
        $status['phone'] = $lead['number_called'];
        $status['phone_id'] = $lead_verified->phone;
        $status['buyer_id'] = !empty($lead['user_buyer_id']) ? intval(Str::of($lead['user_buyer_id'])->replaceMatches('/[^0-9]++/', '')->value()) : null;
        $status['buyer_id'] = ($status['buyer_id'] === 0) ? null : $status['buyer_id'];

        $this->validated_service->validateStatus($status, $lead);
        $this->validated_service->validatePhone($status);
        $this->validated_service->validateBuyer($status, $lead, $provider);
        $this->validated_service->validateDid($status['phone'], $lead, $provider);
        $convertions = $this->track_drive_repository->resourceConvertions($lead, $status, $provider);
        $buyerYP = intval($status['buyer_id'] . env('TRACKDRIVE_PROVIDER_ID'));
        $response = $this->track_drive_repository->saveConvertions($lead_verified, $convertions);

        if ($status['status'] != 'No Contact' && !empty($lead['recording_url'])) {
            $caller_id = $lead['id'] . $provider;
            $result = $this->track_drive_repository->saveRecordings(Convertion::find($caller_id), $lead['recording_url']);
            $shouldTranscribe = $status['buyer_id'] && in_array($buyerYP, $enabledBuyerIds);

            if ($command && $shouldTranscribe && $convertions['durations'] > env('DURATION_TRANSCRIPTION', 60)) {
                $user = auth()->user() ?? Auth::loginUsingId(23, $remember = true);
                TranscriptionJob::dispatch(['id' => $caller_id, 'type' => $convertions['offer_id']], $user)->onQueue('whisper');
                $status_td['status_td'] = $lead['status'];
                $status_td['hold_duration'] = $lead['hold_duration'];
                Recording::find($caller_id)->update(['status' => TranscriptStatusEnum::TRANSCRIBING->value, 'qa_td_status' => $status_td]);
            }

            return $result;
        }

        return $response;
    }
}
