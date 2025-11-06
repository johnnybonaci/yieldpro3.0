<?php

namespace App\Services\Leads;

use App\Models\User;
use App\Models\Leads\Sub;
use App\Models\Leads\Buyer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Leads\DidNumber;
use App\Models\Leads\TrafficSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Repositories\Leads\PubRepository;
use App\Repositories\Leads\OfferRepository;
use App\Repositories\Leads\LeadMetricRepository;

class ValidatedService
{
    public function __construct(
        private PubRepository $pub_repository,
        private LeadMetricRepository $lead_metric_repository,
        private Request $request,
    ) {
    }

    /**
     * Validate Email.
     */
    public function validateEmail(array &$datos): void
    {
        if (Arr::exists($datos, 'email') && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $datos['email'] = 'null@api.com';
        }
    }

    /**
     * Validates Phone.
     */
    public function validatePhone(array &$datos): void
    {
        $datos['phone'] = Str::of($datos['phone'])->replaceMatches('/[^0-9]++/', '')->value();
        $datos['phone'] = Str::startsWith($datos['phone'], '1') && Str::length($datos['phone']) > 10
            ? Str::of($datos['phone'])->substr(1)->value()
            : $datos['phone'];
        $datos['phone'] = intval($datos['phone']);
    }

    /**
     * Validates Pub ID.
     */
    public function validatePub(array &$datos): void
    {
        Log::info('envio de:', [$this->request->user()->email, $this->request->user()->id]);
        $userPub = $this->request->user()->pub_id()->first();
        $pub_ID = $userPub->id;

        if (Arr::exists($datos, 'pub_ID')) {
            $cpl_data = $this->pub_repository->findPubList(intval($datos['pub_ID']));
            if ($cpl_data) {
                $pub_data = $this->pub_repository->getPubId($userPub->offer_id, intval($datos['pub_ID']));
                $pub_ID = $pub_data ? $pub_data->id : $pub_ID;
            }
        }
        $pub_data = $pub_data ?? $this->pub_repository->findById($pub_ID);
        $cpl_data = $cpl_data ?? $this->pub_repository->findPubList($pub_data->pub_list_id);
        $datos['cpl'] = $cpl_data->cpl[$this->request->user()->id] ?? 0;
        $datos['pub_ID'] = $pub_ID;
        $datos['sub_id2'] = $pub_ID;
        $datos['sub_id5'] = $pub_data->pub_list_id;
        $datos['type'] = $this->request->user()->type;
        $datos['sub_id4'] = $datos['type'];
        $datos['yp_lead_id'] = Str::uuid()->toString();
    }

    /**
     * Validates Sub ID exists.
     */
    public function validateSub(array &$datos): void
    {
        $subid_rand = Config::get('services.trackdrive.sub_id');
        $subid_rand = $subid_rand[array_rand($subid_rand)];
        $sub_ID = Sub::firstOrCreate(['sub_id' => $subid_rand])->id;

        if (Arr::exists($datos, 'sub_ID') && !empty($datos['sub_ID'])) {
            $sub_ID = Sub::firstOrCreate(['sub_id' => $datos['sub_ID']])->id;
        }
        $datos['sub_ID'] = $sub_ID;
    }

    /**
     * Validate Campaign Name && Metrics.
     */
    public function validateMetrics(array &$datos): void
    {
        $id = Config::get('services.campaign.' . env('TRACKDRIVE_PROVIDER_ID') . '.' . $datos['type']);
        if (Arr::exists($datos, 'campaign_name') && !empty($datos['campaign_name'])) {
            $id = $this->lead_metric_repository->setCampaignId($datos);
        }
        $datos['campaign_name'] = $id;
        $datos['sub_id3'] = $id;
    }

    /**
     * Validate Did Number.
     */
    public function validateDid(int $phone, array $lead, int $provider): void
    {
        $of_default = $provider == 1 ? 10011 : 20032;
        $tf_default = $provider == 1 ? 1001 : 10002;
        $did = DidNumber::find($phone);
        $traffic_source = !empty($lead['user_traffic_source_id']) ? intval($lead['user_traffic_source_id'] . $provider) : $tf_default;
        TrafficSource::upsert(
            [
                'id' => $traffic_source,
                'name' => $lead['traffic_source'] ?? 'MassNexus',
                'traffic_source_provider_id' => '',
                'provider_id' => $provider,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['id'],
            ['name']
        );

        if (!$did) {
            $insert = [
                'id' => $phone,
                'description' => trim($lead['traffic_source']),
                'offer_id' => !empty($lead['user_offer_id']) ? intval($lead['user_offer_id'] . $provider) : $of_default,
                'traffic_source_id' => $traffic_source,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];
            DidNumber::create($insert);
        }
    }

    /**
     * Validates the pub id without associated user.
     */
    public function validatePubWithoutUser(array &$datos, int $provider): void
    {
        $of_default = $provider == 1 ? 10011 : 20032;
        $default = $provider == 1 ? 67 : 1;

        if ($datos['type'] == 'MC') {
            $of_default = $provider == 1 ? 10001 : 20052;
            $default = $provider == 1 ? 66 : 5;
        }
        if ($datos['type'] == 'legal') {
            $of_default = $provider == 1 ? 10011 : 20032;
            $default = $provider == 1 ? 67 : 1;
        }
        if ($datos['type'] == 'ACA') {
            $of_default = $provider == 1 ? 10021 : 20002;
            $default = $provider == 1 ? 68 : 2;
        }

        $offer_repository = new OfferRepository();
        $offer = $offer_repository->getByType([$datos['type']], $provider)->first();
        $offer_id = $offer ? $offer->id : $of_default;
        $cpl_data = $this->pub_repository->findPubList(intval($datos['pub_id']));
        if ($cpl_data) {
            $pub_data = $this->pub_repository->getPubId($offer_id, intval($datos['pub_id']));
        }
        $pub_data = $pub_data ?? $this->pub_repository->findById($default);
        $cpl_data = $cpl_data ?? $this->pub_repository->findPubList($pub_data->pub_list_id);
        $pub_ID = $pub_data->id;
        $user = User::where('pub_id', $pub_ID)->first();
        $cpl = 0;
        if ($user) {
            $cpl = Arr::exists($cpl_data->cpl, $user->id) ? $cpl_data->cpl[$user->id] : 0;
        }
        $datos['cpl'] = $cpl;
        $datos['pub_id'] = $pub_ID;
        $datos['sub_id2'] = $pub_ID;
        $datos['sub_id5'] = $pub_data->pub_list_id;
        $datos['type'] = $offer ? $offer->type : 'ACA';
        $datos['sub_id4'] = $datos['type'];
    }

    /**
     * Validate TrackDrive API fields.
     */
    public function validateFields(array &$datos, array $data): void
    {
        $datos['first_name'] = $data['token-first_name'] ?? $data['name'];
        $datos['last_name'] = $data['token-last_name'] ?? $data['name'];
        $datos['email'] = !empty($data['token-email']) ? $data['token-email'] : 'null@api.com';
        $datos['yp_lead_id'] = !empty($data['token-yieldpro_lead_id']) ? $data['token-yieldpro_lead_id'] : Str::uuid()->toString();
        $datos['zip_code'] = !empty($data['token-zip']) ? $data['token-zip'] : null;
        $datos['state'] = !empty($data['token-state']) ? $data['token-state'] : $data['caller_city'];
        $datos['ip'] = '127:0:0:1';
        $datos['universal_lead_id'] = !empty($data['token-jornaya_leadid']) ? $data['token-jornaya_leadid'] : null;
    }

    /**
     * Validates call status.
     */
    public function validateStatus(array &$datos, array $data): void
    {
        $status = 'No Contact';
        if ($data['answered_duration'] > 0 && $data['revenue'] <= 0) {
            $status = 'Contact';
        } elseif ($data['revenue'] > 0) {
            $status = 'Billable';
        }
        $datos['status'] = $status;
    }

    /**
     * validates Buyer exists.
     */
    public function validateBuyer(array &$datos, array $data, int $provider): void
    {
        if (!empty($datos['buyer_id'])) {
            Buyer::upsert(
                [
                    'id' => $datos['buyer_id'] . $provider,
                    'name' => $data['buyer'],
                    'buyer_provider_id' => $datos['buyer_id'],
                    'provider_id' => $provider,
                    'created_at' => now(),
                ],
                ['id'],
                ['name', 'buyer_provider_id', 'provider_id']
            );
        }
    }
}
