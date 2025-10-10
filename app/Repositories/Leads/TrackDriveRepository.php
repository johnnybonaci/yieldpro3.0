<?php

namespace App\Repositories\Leads;

use Exception;
use Carbon\Carbon;
use App\Models\Leads\Lead;
use App\Models\Leads\Convertion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class TrackDriveRepository extends LeadApiRepository
{
    public const KEY = 'TRACK_DRIVE_API_KEY_';

    public function __construct()
    {
    }

    /**
     * Summary of Resource.
     */
    public function resource(array $data): Collection
    {
        $offers = $data['offers_data'];
        $provider_id = $data['provider_id'];
        $smid = Config::get('services.trackdrive.smid');
        $smid = $smid[array_rand($smid)];
        $datatd = [
            'lead_token' => env($provider_id . '_' . self::KEY . strtoupper($data['type'])),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'caller_id' => (string) $data['phone'],
            'alternate_phone' => $data['phone'],
            'email' => $data['email'],
            'zip' => $data['zip_code'],
            'traffic_source_id' => $this->getTrafficSourceId($data),
            'sub_id' => $data['pub_id'],
            's1' => $data['pub_id'],
            's2' => $data['sub_id'],
            'lead_type' => $data['type'],
            'SMID' => $smid,
            'source_url' => $offers->get('source_url'),
            'jornaya_leadid' => $data['universal_lead_id'],
            'lead_jornaya' => $data['universal_lead_id'],
            'original_lead_submit_date' => $data['created_at'],
            'yieldpro_lead_id' => $data['yp_lead_id'],
            'created_time' => $data['created_time'] ?? now()->toIsoString(),
            'utm_campaign' => $data['campaign_name_id'],
            'utm_source' => $data['utm_source'],
        ];
        if (!empty($data['state'])) {
            $datatd['custom_state'] = $data['state'];
        }
        if (array_key_exists('white_list', $data) && $data['white_list']) {
            $datatd['whitelist'] = true;
        }
        if (is_array($data['data'])) {
            if (!empty($data['data']['dob'])) {
                $dob = date_parse($data['data']['dob']);

                if ($dob && isset($dob['year'], $dob['month'], $dob['day']) && checkdate($dob['month'], $dob['day'], $dob['year'])) {
                    try {
                        $datatd['dob'] = Carbon::createFromDate($dob['year'], $dob['month'], $dob['day'])->format('Y-m-d');
                    } catch (Exception $e) {
                        Log::error('Error al crear la fecha de nacimiento: ' . $e->getMessage());
                    }
                }
            }
            if (array_key_exists('city', $data['data'])) {
                $datatd['city'] = $data['data']['city'];
            }
            if (array_key_exists('address', $data['data'])) {
                $datatd['address'] = $data['data']['address'];
            }
            if (array_key_exists('gender', $data['data'])) {
                $datatd['gender'] = $data['data']['gender'];
            }
            if (array_key_exists('gclid', $data['data'])) {
                $datatd['gclid'] = $data['data']['gclid'];
            }
            if ($data['pub_id'] == 172) {
                $datatd['costumer_state'] = $data['state'];
            }
        }

        return collect($datatd);
    }

    public function postBack(array $data): ?array
    {
        if ($data['s1'] == '119') {
            $data = [
                'o' => 24710,
                'r' => 'LEAD',
                'd' => $data['yieldpro_lead_id'],
                'i' => $data['sub_id'],
            ];

            return $data;
        }

        return null;
    }

    public function resourceConvertions(array $lead, array $status, int $provider): array
    {
        $tf_default = $provider == 1 ? 10001 : 10002;
        // Format Convertions
        $convertions = [];
        $convertions['id'] = $lead['id'] . $provider;
        $convertions['outside'] = !empty($lead['token-yieldpro_lead_id']) ? 0 : 1;
        $convertions['status'] = $status['status'];
        $convertions['revenue'] = !empty($lead['revenue']) ? $lead['revenue'] : 0;
        $convertions['cpl'] = !empty($lead['payout']) ? $lead['payout'] : 0;
        $convertions['durations'] = intval($lead['answered_duration']);
        $convertions['answered'] = intval($lead['answered_duration']) > 0 ? 1 : 0;
        $convertions['calls'] = 1;
        $convertions['converted'] = ($convertions['revenue'] > 0 || $lead['buyer_converted'] == 'Converted') ? 1 : 0;
        $convertions['terminating_phone'] = $lead['connected_to'];
        $convertions['did_number_id'] = $status['phone'];
        $convertions['phone_id'] = $status['phone_id'];
        $convertions['buyer_id'] = !empty($status['buyer_id']) ? intval($status['buyer_id'] . $provider) : null;
        $convertions['offer_id'] = !empty($lead['user_offer_id']) ? intval($lead['user_offer_id'] . $provider) : null;
        $convertions['traffic_source_id'] = !empty($lead['user_traffic_source_id']) ? intval($lead['user_traffic_source_id'] . $provider) : $tf_default;
        $convertions['created_at'] = Carbon::create($lead['created_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d H:i:s');
        $convertions['date_history'] = Carbon::create($lead['created_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d');
        $convertions['updated_at'] = Carbon::create($lead['updated_at'])->setTimezone(env('TIMEZONE'))->format('Y-m-d H:i:s');

        return $convertions;
    }

    public function saveConvertions(Lead $lead_verified, array $convertions): bool
    {
        return $lead_verified->convertions()->upsert($convertions, ['id'], ['outside', 'answered', 'status', 'revenue', 'cpl', 'durations', 'calls', 'converted', 'terminating_phone', 'did_number_id', 'phone_id', 'buyer_id', 'traffic_source_id', 'offer_id', 'created_at', 'updated_at']);
    }

    public function saveRecordings(Convertion $convertion, string $url): bool
    {
        return $convertion->record()->upsert([
            'id' => $convertion->id,
            'url' => $url,
            'date_history' => $convertion->date_history,
        ], ['id'], ['url', 'date_history']);
    }

    public function getTrafficSourceId(array $data): int
    {
        $tf = $data['pubs']['traffic_source']['id'];

        return $tf;
    }

    public function getCallsMcIbFirst(int $phone, string $order = 'DESC'): ?Convertion
    {
        return Convertion::where('phone_id', $phone)->where('offer_id', 20072)->orderBy('created_at', $order)->first();
    }
}
