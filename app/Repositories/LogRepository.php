<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Leads\LeadLog;
use App\Models\Leads\PhoneRoomLog;
use Illuminate\Support\Facades\Config;
use App\Services\Leads\ValidatedService;
use App\Repositories\Leads\LeadApiRepository;

class LogRepository
{
    public const STATUS = ['error' => 0, 'success' => 1];

    public function __construct(
        private ValidatedService $validated_service,
    ) {
    }

    /**
     * Save logginProviderProvider.
     */
    public function logginProvider(string $response, array $data): void
    {
        $lead_api_repository = new LeadApiRepository();
        $provider = env('TRACKDRIVE_PROVIDER_ID');
        $data_p = $data['data'];
        $lead = $lead_api_repository->findByPhone($data['phone']);
        if (!$lead) {
            $data_p['name'] = $data_p['first_name'];
            $data_p['caller_city'] = 'CA';
            $data_p['token-yieldpro_lead_id'] = $data_p['yieldpro_lead_id'];
            $lead_verified = ['phone' => $data['phone']];
            $lead_verified['pub_id'] = $data_p['s1'];
            $lead_verified['sub_ID'] = $data_p['s2'];
            $lead_verified['type'] = $data_p['lead_type'];
            $lead_verified['campaign_name'] = Config::get('services.campaign.' . $provider . '.' . $data_p['lead_type']);
            $this->validated_service->validatePubWithoutUser($lead_verified, $provider);
            $this->validated_service->validateFields($lead_verified, $data_p);
            $this->validated_service->validateSub($lead_verified);
            $this->validated_service->validateMetrics($lead_verified);
            $lead_verified['sub_id'] = $lead_verified['sub_ID'];
            $lead_verified['campaign_name_id'] = $lead_verified['campaign_name'];
            unset($lead_verified['sub_ID']);
            unset($lead_verified['campaign_name']);
            $lead_verified['created_at'] = Carbon::parse($data_p['original_lead_submit_date'])->format('Y-m-d H:i:s');
            $lead_verified['date_history'] = Carbon::parse($data_p['original_lead_submit_date'])->format('Y-m-d');
            $lead_verified['updated_at'] = Carbon::parse($data_p['original_lead_submit_date'])->format('Y-m-d H:i:s');
            $lead = $lead_api_repository->create(collect($lead_verified));
        }
        LeadLog::create([
            'status' => self::STATUS[$data['status'] ?? 0],
            'log' => $response,
            'provider_id' => $data['provider_id'],
            'phone_id' => $data['phone'],
            'lead_id' => $data['lead_id'] ?? '',
        ]);
    }

    /**
     * Save logginProviderPhoneRoom.
     */
    public function logginPhoneRoom(string $response, array $data, int $phone_room_id): void
    {
        PhoneRoomLog::create([
            'status' => self::STATUS[$data['status'] ?? 0],
            'log' => $response,
            'phone_room_id' => $phone_room_id,
            'phone_id' => $data['phone'],
            'phone_room_lead_id' => $data['lead_id'] ?? '',
            'request' => $data['request'] ?? '',
        ]);
    }
}
