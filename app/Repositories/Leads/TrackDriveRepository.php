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

    /**
     * Summary of Resource.
     */
    public function resource(array $data): Collection
    {
        $offers = $data['offers_data'];
        $provider_id = $data['provider_id'];

        $datatd = $this->buildBaseData($data, $provider_id, $offers);
        $this->addOptionalStateData($datatd, $data);
        $this->addWhitelistData($datatd, $data);
        $this->addExtendedData($datatd, $data);

        return collect($datatd);
    }

    /**
     * Build base tracking data.
     * @param mixed $offers
     */
    private function buildBaseData(array $data, int $provider_id, $offers): array
    {
        $smid = $this->getRandomSmid();

        return [
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
    }

    /**
     * Get random SMID from config.
     */
    private function getRandomSmid(): string
    {
        $smid = Config::get('services.trackdrive.smid');

        return $smid[array_rand($smid)];
    }

    /**
     * Add optional state data.
     */
    private function addOptionalStateData(array &$datatd, array $data): void
    {
        if (!empty($data['state'])) {
            $datatd['custom_state'] = $data['state'];
        }
    }

    /**
     * Add whitelist data if applicable.
     */
    private function addWhitelistData(array &$datatd, array $data): void
    {
        if (array_key_exists('white_list', $data) && $data['white_list']) {
            $datatd['whitelist'] = true;
        }
    }

    /**
     * Add extended data from data array.
     */
    private function addExtendedData(array &$datatd, array $data): void
    {
        if (!is_array($data['data'])) {
            return;
        }

        $this->addDateOfBirth($datatd, $data['data']);
        $this->addOptionalFields($datatd, $data['data']);
        $this->addSpecialPubData($datatd, $data);
    }

    /**
     * Add date of birth if valid.
     */
    private function addDateOfBirth(array &$datatd, array $dataArray): void
    {
        if (empty($dataArray['dob'])) {
            return;
        }

        $dob = date_parse($dataArray['dob']);

        if (!$dob || !isset($dob['year'], $dob['month'], $dob['day'])) {
            return;
        }

        if (!checkdate($dob['month'], $dob['day'], $dob['year'])) {
            return;
        }

        try {
            $datatd['dob'] = Carbon::createFromDate($dob['year'], $dob['month'], $dob['day'])->format('Y-m-d');
        } catch (Exception $e) {
            Log::error('Error al crear la fecha de nacimiento: ' . $e->getMessage());
        }
    }

    /**
     * Add optional fields from data array.
     */
    private function addOptionalFields(array &$datatd, array $dataArray): void
    {
        $optionalFields = ['city', 'address', 'gender', 'gclid'];

        foreach ($optionalFields as $field) {
            if (array_key_exists($field, $dataArray)) {
                $datatd[$field] = $dataArray[$field];
            }
        }
    }

    /**
     * Add special pub-specific data.
     */
    private function addSpecialPubData(array &$datatd, array $data): void
    {
        if ($data['pub_id'] == 172) {
            $datatd['costumer_state'] = $data['state'];
        }
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
