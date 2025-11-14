<?php

namespace App\Services\Leads;

use App\Models\Leads\Pub;
use App\Models\Leads\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Leads\HistoryLeads;
use App\Models\Leads\TrackingLead;
use Illuminate\Support\Collection;
use App\Models\Leads\DuplicateLeads;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Leads\PubRepository;
use App\Repositories\Leads\JornayaLeadRepository;

/**
 * Lead Creation Service.
 *
 * Handles all lead creation, resource building, and validation logic.
 * Extracted from LeadApiRepository to comply with SonarCube standards.
 *
 * Responsibilities:
 * - Create leads in database
 * - Build lead resources from request data
 * - Validate posting permissions
 * - Handle timestamp rotation
 * - Manage duplicate leads
 */
class LeadCreationService
{
    protected JornayaLeadRepository $jornaya_lead_repository;

    /**
     * Save lead history when updated.
     */
    public static function saveHistoryLead(array $lead, array $data): void
    {
        $history = new HistoryLeads();
        $history->before_h = $lead;
        $history->after_h = $data;
        $history->phone_id = $lead['phone'];
        $history->save();
    }

    /**
     * Create or update a lead in the database.
     */
    public function create(Collection $data): Lead
    {
        $chunk = $data->toArray();
        $lead = Lead::FirstOrCreate(['phone' => $chunk['phone']], $chunk);

        unset($chunk['created_time']);
        unset($chunk['sub_id2']);
        unset($chunk['sub_id3']);
        unset($chunk['sub_id4']);
        unset($chunk['sub_id5']);

        TrackingLead::FirstOrCreate(['phone' => $chunk['phone']], $chunk);

        $this->jornaya_lead_repository = new JornayaLeadRepository();
        $this->jornaya_lead_repository->create($lead, $data);

        if (!$lead->wasRecentlyCreated) {
            $this->updateExistingLead($lead, $chunk, $data);
        }

        return $lead;
    }

    /**
     * Update an existing lead with new data.
     */
    protected function updateExistingLead(Lead $lead, array $chunk, Collection $data): void
    {
        $logL = $lead->toArray();

        $lead->universal_lead_id = $chunk['universal_lead_id'] ?? $lead->universal_lead_id;
        $lead->trusted_form = $chunk['trusted_form'] ?? $lead->trusted_form;

        if (!in_array($chunk['pub_id'], [133, 134])) {
            $lead->sub_id = $chunk['sub_id'] ?? $lead->sub_id;
            $lead->pub_id = $chunk['pub_id'] ?? $lead->pub_id;
            $lead->email = $chunk['email'] ?? $lead->email;
        }

        $lead->type = $chunk['type'] ?? $lead->type;
        $lead->campaign_name_id = $chunk['campaign_name_id'] ?? $lead->campaign_name_id;
        $lead->first_name = $chunk['first_name'] ?? $lead->first_name;
        $lead->last_name = $chunk['last_name'] ?? $lead->last_name;
        $lead->date_history = $chunk['date_history'] ?? $lead->date_history;

        self::saveHistoryLead($logL, $data->toArray());

        $lead->updated_at = now();
        $lead->save();

        DuplicateLeads::create($chunk);
    }

    /**
     * Build lead resource from request data.
     */
    public function resource(array $data): Collection
    {
        $response = new Collection();
        $date = now();
        $campaign = $data['campaign_name'] ?? null;

        if (Arr::exists($data, 'phone')) {
            $array = [
                'phone' => $data['phone'],
                'first_name' => $data['firstName'] ?? '',
                'last_name' => $data['lastName'] ?? '',
                'email' => $data['email'] ?? 'null@api.com',
                'type' => $data['type'] ?? 'null',
                'zip_code' => $data['zip_code'] ?? null,
                'state' => $data['state'] ?? null,
                'ip' => $data['ip'] ?? '127:0:0:1',
                'cpl' => $data['cpl'] ?? 0,
                'data' => $data['data'] ?? [],
                'yp_lead_id' => $data['yp_lead_id'] ?? Str::uuid()->toString(),
                'campaign_name_id' => $campaign,
                'utm_source' => $data['utm_source'] ?? null,
                'universal_lead_id' => $data['universal_leadid'] ?? null,
                'trusted_form' => $data['xxTrustedFormToken'] ?? null,
                'sub_id' => $data['sub_ID'] ?? 0,
                'sub_id2' => $data['sub_id2'] ?? $data['pub_ID'],
                'sub_id3' => $data['sub_id3'] ?? $campaign,
                'sub_id4' => $data['sub_id4'] ?? $data['type'],
                'sub_id5' => $data['sub_id5'] ?? $this->getPubId($data['pub_ID']),
                'pub_id' => $data['pub_ID'] ?? 0,
                'dob' => $data['dob'] ?? '',
                'date_history' => $data['date_history'] ?? $date->format('Y-m-d'),
                'created_at' => $data['created_at'] ?? $date->format('Y-m-d H:i:s'),
                'created_time' => $this->rotateTimeStamps($data['pub_ID'] ?? 0),
                'updated_at' => $data['updated_at'] ?? $date->format('Y-m-d H:i:s'),
            ];
            $response = new Collection($array);
        }

        return $response;
    }

    /**
     * Check if a lead can be sent to a provider.
     */
    public function checkPostingLead(Collection $pub_id, Model $model): bool
    {
        $setup = $pub_id->get('setup');
        $string = __toSingularModel($model);
        $result = false;
        $type = $setup[$string]['type'] ?? null;

        if (!$type || $type === 'all') {
            $result = __toCheckSources($setup, $model);
        } elseif ($type === 'interleave') {
            if (__toCheckSources($setup, $model)) {
                $interleave = $pub_id->get('interleave');
                $interleave[$string] = $interleave[$string] ?? 0;

                if ($model->id !== $interleave[$string]) {
                    $interleave[$string] = $model->id;
                    $this->updatePubInterleave($pub_id->get('id'), $interleave);
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Update pub interleave configuration.
     */
    protected function updatePubInterleave(int $pubId, array $interleave): void
    {
        Pub::where('id', $pubId)->update(['interleave' => $interleave]);
    }

    /**
     * Find lead by phone number.
     */
    public function findByPhone(string $phone): ?Lead
    {
        return Lead::find($phone);
    }

    /**
     * Rotate timestamps for specific pub IDs.
     */
    public function rotateTimeStamps(int $pub_id): ?string
    {
        if (in_array($pub_id, [9, 71])) {
            $position = ['1' => 50, '2' => 58, '3' => 65];
            $key = 'time_' . $pub_id;
            $rotate = Cache::get($key, 1);
            $time = $rotate + 1 === 4 ? 1 : $rotate + 1;
            $date = now()->subHours($position[$rotate])->toIsoString();
            Cache::forever($key, $time);

            return $date;
        }

        return now()->toIsoString();
    }

    /**
     * Get pub list ID from pub ID.
     * @param mixed $pub_id
     */
    public function getPubId($pub_id): int
    {
        $pub = new PubRepository();
        $pub = $pub->findById($pub_id);

        if ($pub) {
            return $pub->pub_list_id;
        }

        return 100;
    }
}
