<?php

namespace App\Repositories\Leads;

use Carbon\Carbon;
use App\Models\Leads\Pub;
use App\Models\Leads\Sub;
use App\Models\Leads\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Leads\Convertion;
use App\Models\Leads\HistoryLeads;
use App\Models\Leads\LeadPageView;
use App\Models\Leads\TrackingLead;
use Illuminate\Support\Collection;
use App\Models\Leads\DuplicateLeads;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Collection as PersonalCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LeadApiRepository extends EloquentRepository
{
    public $jornaya_lead_repository;

    public function __construct()
    {
    }

    public static function __saveHistoryLead(array $lead, array $data)
    {
        $history = new HistoryLeads();
        $history->before_h = $lead;
        $history->after_h = $data;
        $history->phone_id = $lead['phone'];
        $history->save();
    }

    /**
     * Summary of create.
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
            self::__saveHistoryLead($logL, $data->toArray());

            $lead->updated_at = now();
            $lead->save();
            DuplicateLeads::create($chunk);
        }

        return $lead;
    }

    /**
     * Resource Leads.
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
     * Check if a leads can be sent to a provider.
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
                    $this->updateById($pub_id->get('id'), new Pub(), ['interleave' => $interleave]);
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Summary of findByPhone.
     */
    public function findByPhone(string $phone): ?Lead
    {
        return Lead::find($phone);
    }

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
     * Return Totals Leads from date start & date end.
     */
    public function leads(string $date_start, string $date_end): Builder
    {
        $date_record = request()->input('date_record', 'date_created');
        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();

        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $col = [$table . '.phone', $table . '.first_name', $table . '.last_name', $table . '.email', $table . '.type', $table . '.zip_code', $table . '.state', $table . '.data', $table . '.yp_lead_id', $table . '.campaign_name_id', $table . '.universal_lead_id', $table . '.trusted_form', 'subs.sub_id', 'pubs.pub_list_id', $table . '.created_at', $table . '.cpl', 'pub_lists.name as vendors_yp', 'offers.name as offers', 'convertions.calls', 'convertions.status', $table . '.sub_id5'];
        if ($date_record == 'date_created') {
            $leads = Convertion::rightJoin($table, function ($join) use ($date_start, $date_end, $table) {
                $join->on($table . '.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', $table . '.sub_id')
                ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
                ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
                ->select($col)
                ->whereBetween($table . '.date_history', [$date_start, $date_end])
                ->whereNotIn($table . '.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy($table . '.phone');
        } else {
            $leads = Convertion::join($table, function ($join) use ($date_start, $date_end, $table) {
                $join->on($table . '.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', $table . '.sub_id')
                ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
                ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
                ->select($col)
                ->whereNotBetween($table . '.date_history', [$date_start, $date_end])
                ->whereNotIn($table . '.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy($table . '.phone');
        }

        return $leads;
    }

    public function history(string $date_start, string $date_end): PersonalCollection
    {
        $data = HistoryLeads::selectRaw('before_h, after_h, phone_id, created_at')
            ->whereBetween('created_at', [$date_start . ' 00:00:00', $date_end . ' 23:59:59'])
            ->filterFields()
            ->sortsFields('created_at')->get();
        $pub = Pub::all()->pluck('pub_list_id', 'id');
        $pub = $pub->all();
        $sub = Sub::all()->pluck('sub_id', 'id');
        $sub = $sub->all();
        $data = $data->map(function ($item) use ($pub, $sub) {
            $before_h = $item->before_h;
            $after_h = $item->after_h;
            $before_h['pub_id'] = $pub[$before_h['pub_id']];
            $after_h['pub_id'] = $pub[$after_h['pub_id']];
            $before_h['sub_id'] = $sub[$before_h['sub_id']];
            $after_h['sub_id'] = $sub[$after_h['sub_id']];

            return [
                'before_h' => $before_h,
                'after_h' => $after_h,
                'phone_id' => $item->phone_id,
                'created_at' => $item->created_at,
            ];
        });

        return new PersonalCollection($data);
    }

    public function historyNew(string $date_start, string $date_end): PersonalCollection
    {
        $data = HistoryLeads::selectRaw('before_h, after_h, phone_id, created_at, updated_at')
            ->whereBetween('created_at', [$date_start . ' 00:00:00', $date_end . ' 23:59:59'])
            ->filterFields()
            ->sortsFieldsAsc('created_at')
            ->get();

        $pub = Pub::all()->pluck('pub_list_id', 'id')->all();
        $sub = Sub::all()->pluck('sub_id', 'id')->all();

        $data = $data->groupBy('phone_id')->map(function ($items, $phone_id) use ($pub, $sub) {
            $lastUpdate = $items->max('created_at');
            $dateCreated = Carbon::parse($items->first()->before_h['created_time'])->format('Y-m-d');

            $lastAfterH = null;

            $datos = $items->map(function ($item) use ($pub, $sub, &$lastAfterH) {
                $before_h = $item->before_h;
                $after_h = $item->after_h;

                $before_h = Arr::except($before_h, ['created_at', 'created_time']);
                $after_h = Arr::except($after_h, ['created_at', 'created_time']);

                $before_h['pub_id'] = $pub[$before_h['pub_id']] ?? null;
                $after_h['pub_id'] = $pub[$after_h['pub_id']] ?? null;
                $before_h['sub_id'] = $sub[$before_h['sub_id']] ?? null;
                $after_h['sub_id'] = $sub[$after_h['sub_id']] ?? null;

                if ($lastAfterH) {
                    $before_h['updated_at'] = $lastAfterH['updated_at'];
                }

                $lastAfterH = ['updated_at' => $item->updated_at];

                return [
                    'before_h' => $before_h,
                    'after_h' => $after_h,
                    'phone' => $item->phone_id,
                    'created_at' => $item->created_at,
                ];
            });

            return [
                'data' => $datos->toArray(),
                'phone' => $phone_id,
                'last_update' => $lastUpdate,
                'date_created' => $dateCreated,
            ];
        });

        return new PersonalCollection($data);
    }

    /**
     * returm average widget.
     */
    public function average(string $date_start, string $date_end): array
    {
        $date_record = request()->input('date_record', 'date_created');
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted, sum(convertions.answered) as answered,count(DISTINCT(convertions.phone_id)) as unique_calls';
        $out_count = 0;
        $out_cpl = 0;

        if ($date_record == 'date_created') {
            $totals_convertions = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, true, false);
            $leads_sale_in = $this->getCplIn($date_start, $date_end, $pubs_lists);
            $out_count = $leads_sale_in->count();
            $out_cpl = $leads_sale_in->sum('cpl');
        } else {
            $totals_convertions = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, false, true);
            $leads_sale_out = $this->getCplOut($date_start, $date_end, $pubs_lists);
            $out_count = $leads_sale_out->count();
            $out_cpl = $leads_sale_out->sum('cpl');
        }
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateAverage($totals_convertions, $total_leads);
    }

    public function getCplOut(string $date_start, string $date_end, array $pubs_lists, bool $not = true): ?EloquentCollection
    {
        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();

        return Convertion::join($table, function ($join) use ($date_start, $date_end, $table) {
            $join->on($table . '.phone', '=', 'convertions.phone_id')
                ->whereBetween('convertions.date_history', [$date_start, $date_end]);
        })
            ->join('subs', 'subs.id', '=', $table . '.sub_id')
            ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->selectRaw('MAX(' . $table . '.cpl) as cpl')
            ->whereNotBetween($table . '.date_history', [$date_start, $date_end])
            ->when($not, fn ($query) => $query->whereNotIn($table . '.pub_id', $pubs_lists))
            ->filterFields()
            ->groupBy($table . '.phone')
            ->get();
    }

    public function getCplIn(string $date_start, string $date_end, array $pubs_lists, bool $not = true): ?EloquentCollection
    {
        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();

        return Convertion::rightJoin($table, function ($join) use ($date_start, $date_end, $table) {
            $join->on($table . '.phone', '=', 'convertions.phone_id')
                ->whereBetween('convertions.date_history', [$date_start, $date_end]);
        })
            ->join('subs', 'subs.id', '=', $table . '.sub_id')
            ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->selectRaw('MAX(' . $table . '.cpl) as cpl')
            ->whereBetween($table . '.date_history', [$date_start, $date_end])
            ->when($not, fn ($query) => $query->whereNotIn($table . '.pub_id', $pubs_lists))
            ->filterFields()
            ->groupBy($table . '.phone')
            ->get();
    }

    public function getCplInMn(string $date_start, string $date_end, array $pubs_lists, bool $not = true)
    {
        return Convertion::rightJoin('leads', function ($join) use ($date_start, $date_end) {
            $join->on('leads.phone', '=', 'convertions.phone_id')
                ->whereBetween('convertions.date_history', [$date_start, $date_end]);
        })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->selectRaw('MAX(leads.cpl) as cpl')
            ->whereBetween('leads.date_history', [$date_start, $date_end])
            ->when($not, fn ($query) => $query->whereNotIn('leads.pub_id', $pubs_lists))
            ->where(function ($q) {
                $q->where('convertions.traffic_source_id', 10002)
                    ->orWhereNull('convertions.traffic_source_id');
            })
            ->filterFields()
            ->groupBy('leads.phone')
            ->get();
    }

    public function getTotalConvertions(string $date_start, string $date_end, string $columns, array $pubs_lists, bool $sale, bool $date, bool $not = true): ?Convertion
    {
        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();

        return Convertion::selectRaw($columns)
            ->join($table, function ($join) use ($date_start, $date_end, $table) {
                $join->on($table . '.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', $table . '.sub_id')
            ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->when($sale, fn ($query) => $query->whereBetween($table . '.date_history', [$date_start, $date_end]))
            ->when($date, fn ($query) => $query->whereNotBetween($table . '.date_history', [$date_start, $date_end]))
            ->when($not, fn ($query) => $query->whereNotIn($table . '.pub_id', $pubs_lists))
            ->filterFields()
            ->first();
    }

    public function getTotalConvertionsCampaign(string $date_start, string $date_end, bool $sale, bool $date): array
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';
        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();
        $columns_group = 'campaign_name_id, subs.sub_id, pubs.pub_list_id,pub_lists.name as vendors_yp,traffic_sources.name as vendors_td,sub_id2,sub_id3,sub_id4,leads.pub_id,traffic_sources.id as traffic_source_id';
        $columns_empty = ' "" as campaign_name_id, "" as sub_id, "" as pub_list_id, "" as  vendors_yp, "" as vendors_td';
        $columns = $view_by . ' as view_by, sum(revenue) AS revenue,sum(convertions.cpl) AS cpl,sum(calls) AS calls, sum(converted) AS converted, 0 as leads, sum(answered) as answered,' . $table . '.type';
        $group_by = $view_by == 'leads.campaign_name_id' ? ['leads.campaign_name_id', 'leads.sub_id3', 'leads.pub_id', 'convertions.traffic_source_id'] : $view_by;

        return $model::selectRaw($columns)
            ->when($view_by == $table . '.type', fn ($query) => $query->selectRaw($columns_empty))
            ->when($view_by != $table . '.type', fn ($query) => $query->selectRaw($columns_group))
            ->join('convertions', function ($join) use ($date_start, $date_end, $table) {
                $join->on($table . '.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', $table . '.sub_id')
            ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->when($sale, fn ($query) => $query->whereBetween($table . '.date_history', [$date_start, $date_end]))
            ->when($date, fn ($query) => $query->whereNotBetween($table . '.date_history', [$date_start, $date_end]))
            ->filterFields()
            ->groupBy($group_by)
            ->get()
            ->toArray();
    }

    public function sumAverage(string $date_start, string $date_end, array $average): array
    {
        $date_record = request()->input('date_record', 'date_created');
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted';
        $out_count = 0;
        $out_cpl = 0;

        if ($date_record == 'date_created') {
            $totals_convertions = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, false, true);
            $leads_sale_out = $this->getCplOut($date_start, $date_end, $pubs_lists);
            $out_count = $leads_sale_out->count();
            $out_cpl = $leads_sale_out->sum('cpl');
        } else {
            $totals_convertions = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, true, false);
            $leads_sale_in = $this->getCplIn($date_start, $date_end, $pubs_lists);
            $out_count = $leads_sale_in->count();
            $out_cpl = $leads_sale_in->sum('cpl');
        }
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateSumAverage($totals_convertions, $total_leads, $average);
    }

    /**
     * returm average widget.
     */
    public function fastAverage(string $date_start, string $date_end): array
    {
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted,sum(convertions.answered) as answered,count(DISTINCT(convertions.phone_id)) as unique_calls';
        $out_count = 0;
        $out_cpl = 0;

        $totals_convertions_c = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, true, false, false);
        $leads_sale_in = $this->getCplIn($date_start, $date_end, $pubs_lists, false);
        $out_count = $leads_sale_in->count();
        $out_cpl = $leads_sale_in->sum('cpl');

        $totals_convertions_s = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, false, true, false);
        $leads_sale_out = $this->getCplOut($date_start, $date_end, $pubs_lists, false);
        $out_count = $out_count + $leads_sale_out->count();

        $totals_convertions = (object) ['revenue' => $totals_convertions_c->revenue + $totals_convertions_s->revenue, 'cpl' => $totals_convertions_c->cpl + $totals_convertions_s->cpl, 'calls' => $totals_convertions_c->calls + $totals_convertions_s->calls, 'converted' => $totals_convertions_c->converted + $totals_convertions_s->converted, 'answered' => $totals_convertions_c->answered + $totals_convertions_s->answered, 'unique_calls' => $totals_convertions_c->unique_calls + $totals_convertions_s->unique_calls];
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateAverage($totals_convertions, $total_leads);
    }

    /**
     * returm average widget.
     */
    public function fastAverageMn(string $date_start, string $date_end): array
    {
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted, sum(convertions.answered) as answered,count(DISTINCT(convertions.phone_id)) as unique_calls';
        $out_count = 0;
        $out_cpl = 0;

        $totals_convertions_c = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, true, false, false);
        $totals_convertions_s = $this->getTotalConvertions($date_start, $date_end, $columns, $pubs_lists, false, true, false);

        $leads_sale_out = $this->getCplOut($date_start, $date_end, $pubs_lists, false);
        $out_count = $leads_sale_out->count();

        request()->offsetUnset('convertions_traffic1source1id');

        $leads_sale_in = $this->getCplInMn($date_start, $date_end, $pubs_lists, false);
        $out_count = $out_count + $leads_sale_in->count();
        $out_cpl = $leads_sale_in->sum('cpl');

        $totals_convertions = (object) ['revenue' => $totals_convertions_c->revenue + $totals_convertions_s->revenue, 'cpl' => $totals_convertions_c->cpl + $totals_convertions_s->cpl, 'calls' => $totals_convertions_c->calls + $totals_convertions_s->calls, 'converted' => $totals_convertions_c->converted + $totals_convertions_s->converted, 'answered' => $totals_convertions_c->answered + $totals_convertions_s->answered, 'unique_calls' => $totals_convertions_c->unique_calls + $totals_convertions_s->unique_calls];
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateAverage($totals_convertions, $total_leads);
    }

    /**
     * calculate Average from leads.
     */
    public function calculateAverage(object $totals_convertions, object $total_leads): array
    {
        $spend = $totals_convertions->cpl + $total_leads->cpl;
        $revenue = $totals_convertions->revenue ?? 0;
        $calls = $totals_convertions->calls ?? 0;
        $answered = $totals_convertions->answered ?? 0;
        $converted = $totals_convertions->converted ?? 0;
        $profit = $revenue - $spend;
        $leads = $total_leads->leads;
        $unique_calls = $totals_convertions->unique_calls ?? 0;
        $var = ['spend' => $spend, 'revenue' => $revenue, 'calls' => $calls, 'converted' => $converted, 'profit' => $profit, 'leads' => $leads, 'answered' => $answered, 'unique_calls' => $unique_calls];

        return $this->setAverage($var, 'average', $total_leads->cpl, $totals_convertions->cpl);
    }

    /**
     * Calculate sum average from leads.
     */
    public function calculateSumAverage(object $totals_convertions, object $total_leads, array $average): array
    {
        $spend = request()->input('date_record', 'date_created') == 'date_created' ? $average['sum']['total_spend'] : $totals_convertions->cpl;
        $revenue = ($totals_convertions->revenue + $average['sum']['total_revenue']) ?? 0;
        $calls = ($totals_convertions->calls + $average['average']['total_calls']) ?? 0;
        $converted = ($totals_convertions->converted + $average['average']['total_billable']) ?? 0;
        $answered = ($totals_convertions->answered + $average['average']['total_answered']) ?? 0;
        $profit = $revenue - $spend;
        $leads = $total_leads->leads + $average['average']['total_leads'];
        $var = ['spend' => $spend, 'revenue' => $revenue, 'calls' => $calls, 'converted' => $converted, 'profit' => $profit, 'leads' => $leads, 'answered' => $answered];

        return $this->setAverage($var, 'totals_avg', $total_leads->cpl, $totals_convertions->cpl);
    }

    /**
     * Calculate average from leads.
     */
    public function setAverage(array $var, string $name, ?float $leads_cpl, ?float $convertions_cpl): array
    {
        extract($var);
        $array = [
            $name => [
                'total_spend' => round($spend, 2),
                'total_revenue' => round($revenue, 1),
                'total_profit' => round($profit, 2),
                'total_roi' => $spend > 0 ? round(($profit / $spend) * 100, 2) : 0,
                'total_convertion' => $unique_calls > 0 ? round(($converted / $unique_calls) * 100, 2) : 0,
                'total_leadstocall' => $calls > 0 ? round($leads / $calls, 2) : 0,
                'total_leads' => $leads,
                'total_calls' => $calls,
                'total_answered' => $answered,
                'total_billable' => $converted,
                'total_answeredtobillable' => $answered > 0 ? round(($converted / $answered) * 100, 2) : 0,
                'total_callstoanswered' => $calls > 0 ? round(($answered / $calls) * 100, 2) : 0,
                'total_cpl' => round($leads > 0 ? $spend / $leads : 0, 2),
                'total_cpc' => round($answered > 0 ? $spend / $answered : 0, 2),
                'total_cps' => round($converted > 0 ? $spend / $converted : 0, 2),
                'total_rpl' => round($leads > 0 ? $revenue / $leads : 0, 2),
                'total_rpc' => round($unique_calls > 0 ? $revenue / $unique_calls : 0, 2),
                'total_rps' => round($converted > 0 ? $revenue / $converted : 0, 2),
                'total_spend_leads' => round($leads_cpl, 2),
                'total_spend_calls' => round($convertions_cpl, 2),
                'total_uniquecalls' => $unique_calls,
            ],
        ];
        if ($name == 'average') {
            $array['sum']['total_revenue'] = round($revenue, 2);
            $array['sum']['total_spend'] = round($spend, 2);
        }

        return $array;
    }

    /**
     * returm total leads records.
     */
    public function records(string $date_start, string $date_end): int
    {
        $date_record = request()->input('date_record', 'date_created');
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];
        if ($date_record == 'date_created') {
            $a = Convertion::rightJoin('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', 'leads.sub_id')
                ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->selectRaw('count(leads.phone) as leads')
                ->whereBetween('leads.date_history', [$date_start, $date_end])
                ->whereNotIn('leads.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy('leads.phone')
                ->get();
        } else {
            $a = Convertion::join('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', 'leads.sub_id')
                ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->selectRaw('count(leads.phone) as leads')
                ->whereNotBetween('leads.date_history', [$date_start, $date_end])
                ->whereNotIn('leads.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy('leads.phone')
                ->get();
        }

        return $a->count();
    }

    /**
     * returm list data Campaign Dashboard.
     */
    public function campaignDashboard(string $date_start, string $date_end): PersonalCollection
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';
        [$table, $fields] = Str::of($view_by)->explode('.')->toArray();
        $fields = $view_by == 'leads.campaign_name_id' ? 'cm_pub' : $fields;
        $totals_convertions_c = $this->getTotalConvertionsCampaign($date_start, $date_end, true, false);
        $totals_convertions_s = $this->getTotalConvertionsCampaign($date_start, $date_end, false, true);

        $total_leads_cpl_c = $this->getTotalLeadsCampaign($date_start, $date_end, true, false);
        $total_leads_cpl_s = $this->getTotalLeadsCampaign($date_start, $date_end, false, true);

        $total_leads_cpl_c = $total_leads_cpl_c->merge($total_leads_cpl_s);
        $total_leads_cpl = $total_leads_cpl_c->groupBy($fields)->map(function ($item) use ($fields) {
            if ($fields != 'sub_id3') {
                $array['campaign_name_id'] = $item->first()['campaign_name_id'];
                $array['vendors_yp'] = $item->first()['vendors_yp'];
                $array['sub_id'] = $item->first()['sub_id'];
                $array['pub_id'] = $item->first()['pub_id'];
                $array['sub_id3'] = $item->first()['sub_id3'];
                $array['sub_id2'] = $item->first()['sub_id2'];
                $array['sub_id4'] = $item->first()['sub_id4'];
                $array['pub_list_id'] = $item->first()['pub_list_id'];
                $array['traffic_source_id'] = $item->first()['traffic_source_id'];
            } else {
                $array['campaign_name_id'] = '';
                $array['vendors_yp'] = '';
                $array['sub_id'] = '';
                $array['pub_id'] = '';
                $array['sub_id3'] = $item->first()['sub_id3'];
                $array['sub_id2'] = '';
                $array['sub_id4'] = '';
                $array['pub_list_id'] = '';
                $array['traffic_source_id'] = '';
            }
            $array['cpl'] = $item->sum('cpl');
            $array['type'] = $item->first()['type'];
            $array['leads'] = $item->sum('leads');
            $array['view_by'] = $item->first()['view_by'];

            return $array;
        })->values();
        $totals_convertions = array_merge($totals_convertions_c, $totals_convertions_s);
        $totals_convertions = new Collection($totals_convertions);

        $total_leads_cpl = $total_leads_cpl->map(function ($item) use ($totals_convertions, $fields) {
            $array = [];
            if ($fields == 'cm_pub') {
                $data = $totals_convertions->where('campaign_name_id', $item['campaign_name_id'])->where('sub_id3', $item['sub_id3'])->where('pub_id', $item['pub_id'])->where('traffic_source_id', $item['traffic_source_id']);
            } else {
                $data = $totals_convertions->where($fields, $item[$fields]);
            }
            if ($data->count() > 0) {
                $data = $data->values();
                $array = $data[0];
                $array['cpl'] = $data->sum('cpl') + $item['cpl'];
                $array['cpl_calls'] = $data->sum('cpl');
                $array['cpl_leads'] = $item['cpl'];
                $array['revenue'] = $data->sum('revenue');
                $array['answered'] = $data->sum('answered');
                $array['calls'] = $data->sum('calls');
                $array['converted'] = $data->sum('converted');
                $array['leads'] = $item['leads'];
            } else {
                if ($fields != 'sub_id3') {
                    $array['campaign_name_id'] = $item['campaign_name_id'];
                    $array['sub_id'] = $item['sub_id'];
                    $array['sub_id2'] = $item['sub_id2'];
                    $array['sub_id3'] = $item['sub_id3'];
                    $array['sub_id4'] = $item['sub_id4'];
                    $array['pub_list_id'] = $item['pub_list_id'];
                    $array['vendors_yp'] = $item['vendors_yp'];
                } else {
                    $array['campaign_name_id'] = '';
                    $array['sub_id'] = '';
                    $array['sub_id2'] = '';
                    $array['sub_id3'] = $item['sub_id3'];
                    $array['sub_id4'] = '';
                    $array['pub_list_id'] = '';
                    $array['vendors_yp'] = '';
                }
                $array['vendors_td'] = '';
                $array['view_by'] = $item['view_by'];
                $array['revenue'] = '0.00';
                $array['cpl'] = $item['cpl'];
                $array['cpl_calls'] = 0;
                $array['cpl_leads'] = $item['cpl'];
                $array['calls'] = '0';
                $array['converted'] = '0';
                $array['answered'] = '0';
                $array['type'] = $item['type'];
                $array['leads'] = $item['leads'];
            }

            return $array;
        })->filter();

        return new PersonalCollection($this->sortCollection($total_leads_cpl));
    }

    public function getTotalLeadsCampaign(string $date_start, string $date_end, bool $sale, bool $date, bool $ts = false)
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';

        $model = request()->input('url_switch') == 'tracking-campaign' ? new TrackingLead() : new Lead();
        $table = $model->getTable();

        $columns_cpl = $view_by . ' as view_by,subs.sub_id, pubs.pub_list_id, 1 as leads,CONCAT(leads.campaign_name_id,"",leads.sub_id3,"",leads.pub_id,"",ifnull(convertions.traffic_source_id,66666)) as cm_pub,campaign_name_id,pub_lists.name as vendors_yp,' . $table . '.type,leads.sub_id3,leads.sub_id2,leads.sub_id4,ifnull(convertions.traffic_source_id,66666) as traffic_source_id,leads.pub_id';
        $group_by = ['leads.phone', 'leads.campaign_name_id', 'leads.sub_id3', 'leads.pub_id', 'convertions.traffic_source_id'];

        return Convertion::selectRaw($columns_cpl)
            ->when(
                $sale,
                fn ($query) => $query->rightJoin($table, function ($join) use ($date_start, $date_end, $table) {
                    $join->on($table . '.phone', '=', 'convertions.phone_id')
                        ->whereBetween('convertions.date_history', [$date_start, $date_end]);
                })->whereBetween($table . '.date_history', [$date_start, $date_end])->selectRaw('MAX(' . $table . '.cpl) as cpl')
            )
            ->when(
                $date,
                fn ($query) => $query->join($table, function ($join) use ($date_start, $date_end, $table) {
                    $join->on($table . '.phone', '=', 'convertions.phone_id')
                        ->whereBetween('convertions.date_history', [$date_start, $date_end]);
                })->whereNotBetween($table . '.date_history', [$date_start, $date_end])->selectRaw('0 as cpl')
            )
            ->when(
                $ts,
                fn ($query) => $query->where(function ($q) {
                    $q->where('convertions.traffic_source_id', 10002)
                        ->orWhereNull('convertions.traffic_source_id');
                })
            )
            ->join('subs', 'subs.id', '=', $table . '.sub_id')
            ->join('pubs', 'pubs.id', '=', $table . '.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->filterFields()
            ->groupBy($group_by)
            ->get()
            ->collect();
    }

    public function sortCollection(Collection $collection): Collection
    {
        $sort = request()->input('sort', [['field' => 'gross_revenue', 'dir' => 'desc']]);
        $pubs = Pub::join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->select('pubs.id')
            ->selectRaw('CONCAT(pub_lists.name,"-",pubs.pub_list_id) as vendors_yp')
            ->get()
            ->pluck('vendors_yp', 'id')
            ->toArray();
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;

        return $collection->map(function ($item) use ($pubs) {
            return [
                'campaign_name' => $item['campaign_name_id'],
                'vendors_yp' => $item['vendors_yp'],
                'vendors_td' => $item['vendors_td'],
                'sub_id2' => $pubs[$item['sub_id2']] ?? '',
                'sub_id3' => $item['sub_id3'],
                'sub_id4' => $item['sub_id4'],
                'type' => $item['type'],
                'pub_id' => $item['pub_list_id'],
                'sub_id' => $item['sub_id'],
                'total_leads' => $item['leads'] ?? 0,
                'total_calls' => $item['calls'] ?? 0,
                'total_answered' => $item['answered'] ?? 0,
                'total_sales' => $item['converted'] ?? 0,
                'total_spend' => round($item['cpl'], 2),
                'total_spend_leads' => round($item['cpl_leads'], 2),
                'total_spend_calls' => round($item['cpl_calls'], 2),
                'gross_revenue' => round($item['revenue'], 2),
                'gross_profit' => round($item['revenue'] - $item['cpl'], 2),
                'gross_margin' => $item['cpl'] > 0 ? round((($item['revenue'] - $item['cpl']) / $item['cpl']) * 100, 2) : 0,
                'cost_per_lead' => $item['leads'] > 0 ? round($item['cpl'] / $item['leads'], 2) : 0,
                'rev_per_lead' => $item['revenue'] > 0 ? round($item['cpl'] / $item['revenue'], 2) : 0,
                'cost_per_calls' => $item['calls'] > 0 ? round($item['cpl'] / $item['calls'], 2) : 0,
                'rev_per_calls' => $item['revenue'] > 0 ? round($item['cpl'] / $item['revenue'], 2) : 0,
                'cost_per_sales' => $item['converted'] > 0 ? round($item['cpl'] / $item['converted'], 2) : 0,
                'revenue_per_sale' => $item['converted'] > 0 ? round($item['revenue'] / $item['converted'], 2) : 0,
                'revenue_per_call' => $item['calls'] > 0 ? round($item['revenue'] / $item['calls'], 2) : 0,
                'call_per' => $item['leads'] > 0 ? round($item['calls'] / $item['leads'], 2) : 0,
                'cpa_per' => $item['calls'] > 0 ? round($item['converted'] / $item['calls'], 2) : 0,
            ];
        })->sortBy($fields, SORT_REGULAR, $dir)->values();
    }

    public function calculateDiff(string $start, string $end, array $totals, $campaign = null, $call = null): array
    {
        $avg = 'average';
        if ($campaign) {
            $average = $call ? $call : $this->fastAverage($start, $end);
        } else {
            $average = $this->average($start, $end);
        }
        $totals['totals_diff'] = $totals[$avg];
        $totals['totals_diff']['total_profit'] = $average[$avg]['total_profit'] != 0 ? round((($totals[$avg]['total_profit'] - $average[$avg]['total_profit']) / $average[$avg]['total_profit']) * 100, 1) : 0;
        $totals['totals_diff']['total_revenue'] = $average[$avg]['total_revenue'] != 0 ? round((($totals[$avg]['total_revenue'] - $average[$avg]['total_revenue']) / $average[$avg]['total_revenue']) * 100, 1) : 0;
        $totals['totals_diff']['total_spend'] = $average[$avg]['total_spend'] != 0 ? round((($totals[$avg]['total_spend'] - $average[$avg]['total_spend']) / $average[$avg]['total_spend']) * 100, 1) : 0;
        $totals['totals_diff']['total_roi'] = $average[$avg]['total_roi'] != 0 ? round((($totals[$avg]['total_roi'] - $average[$avg]['total_roi']) / $average[$avg]['total_roi']) * 100, 1) : 0;
        $totals['totals_diff']['total_leads'] = $average[$avg]['total_leads'] != 0 ? round((($totals[$avg]['total_leads'] - $average[$avg]['total_leads']) / $average[$avg]['total_leads']) * 100, 1) : 0;
        $totals['totals_diff']['total_calls'] = $average[$avg]['total_calls'] != 0 ? round((($totals[$avg]['total_calls'] - $average[$avg]['total_calls']) / $average[$avg]['total_calls']) * 100, 1) : 0;
        $totals['totals_diff']['total_billable'] = $average[$avg]['total_billable'] != 0 ? round((($totals[$avg]['total_billable'] - $average[$avg]['total_billable']) / $average[$avg]['total_billable']) * 100, 1) : 0;
        $totals['totals_diff']['total_convertion'] = $average[$avg]['total_convertion'] != 0 ? round((($totals[$avg]['total_convertion'] - $average[$avg]['total_convertion']) / $average[$avg]['total_convertion']) * 100, 1) : 0;

        unset($totals[$avg]);

        return $totals;
    }

    public function calculateDiffMn(string $start, string $end, array $totals, $campaign = null, $call = null): array
    {
        $avg = 'totals_avg';
        if ($campaign) {
            $avg = 'average';
            $oldTotal = $call ? $call : $this->fastAverageMn($start, $end);
        } else {
            $average = $this->average($start, $end);
            $oldTotal = $this->sumAverage($start, $end, $average);
        }
        $totals['totals_diff'] = $totals[$avg];
        $totals['totals_diff']['total_profit'] = $oldTotal[$avg]['total_profit'] != 0 ? round((($totals[$avg]['total_profit'] - $oldTotal[$avg]['total_profit']) / $oldTotal[$avg]['total_profit']) * 100, 1) : 0;
        $totals['totals_diff']['total_revenue'] = $oldTotal[$avg]['total_revenue'] != 0 ? round((($totals[$avg]['total_revenue'] - $oldTotal[$avg]['total_revenue']) / $oldTotal[$avg]['total_revenue']) * 100, 1) : 0;
        $totals['totals_diff']['total_spend'] = $oldTotal[$avg]['total_spend'] != 0 ? round((($totals[$avg]['total_spend'] - $oldTotal[$avg]['total_spend']) / $oldTotal[$avg]['total_spend']) * 100, 1) : 0;
        $totals['totals_diff']['total_roi'] = $oldTotal[$avg]['total_roi'] != 0 ? round((($totals[$avg]['total_roi'] - $oldTotal[$avg]['total_roi']) / $oldTotal[$avg]['total_roi']) * 100, 1) : 0;
        $totals['totals_diff']['total_leads'] = $oldTotal[$avg]['total_leads'] != 0 ? round((($totals[$avg]['total_leads'] - $oldTotal[$avg]['total_leads']) / $oldTotal[$avg]['total_leads']) * 100, 1) : 0;
        $totals['totals_diff']['total_calls'] = $oldTotal[$avg]['total_calls'] != 0 ? round((($totals[$avg]['total_calls'] - $oldTotal[$avg]['total_calls']) / $oldTotal[$avg]['total_calls']) * 100, 1) : 0;
        $totals['totals_diff']['total_billable'] = $oldTotal[$avg]['total_billable'] != 0 ? round((($totals[$avg]['total_billable'] - $oldTotal[$avg]['total_billable']) / $oldTotal[$avg]['total_billable']) * 100, 1) : 0;
        $totals['totals_diff']['total_convertion'] = $oldTotal[$avg]['total_convertion'] != 0 ? round((($totals[$avg]['total_convertion'] - $oldTotal[$avg]['total_convertion']) / $oldTotal[$avg]['total_convertion']) * 100, 1) : 0;

        unset($totals[$avg]);

        return $totals;
    }

    public function pagewidgets(string $date_start, string $date_end): array
    {
        $data['linkout'] = LeadPageView::whereBetween('date_history', [$date_start, $date_end])
            ->where('campaign_name', 'inbounds-linkout')
            ->filterFields()
            ->count();
        $data['coreg'] = LeadPageView::whereBetween('date_history', [$date_start, $date_end])
            ->where('campaign_name', 'inbounds-coreg')
            ->filterFields()
            ->count();
        $data['other'] = LeadPageView::whereBetween('date_history', [$date_start, $date_end])
            ->whereNotIn('campaign_name', ['inbounds-coreg', 'inbounds-linkout'])
            ->filterFields()
            ->count();

        return ['totals' => $data];
    }

    /**
     * returm list data Campaign Dashboard.
     */
    public function campaignMn(string $date_start, string $date_end): PersonalCollection
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';

        [$table, $fields] = Str::of($view_by)->explode('.')->toArray();
        $fields = $view_by == 'subs.sub_id' ? 'sub_pub' : $fields;

        $totals_convertions_c = $this->getTotalConvertionsCampaign($date_start, $date_end, true, false);
        $totals_convertions_s = $this->getTotalConvertionsCampaign($date_start, $date_end, false, true);

        $total_leads_cpl_s = $this->getTotalLeadsCampaign($date_start, $date_end, false, true);
        request()->offsetUnset('convertions_traffic1source1id');
        $total_leads_cpl_c = $this->getTotalLeadsCampaign($date_start, $date_end, true, false, true);
        $total_leads_cpl_c = $total_leads_cpl_c->merge($total_leads_cpl_s);
        $total_leads_cpl = $total_leads_cpl_c->groupBy($fields)->map(function ($item) use ($fields) {
            if ($fields != 'type') {
                $array['campaign_name_id'] = $item->first()['campaign_name_id'];
                $array['vendors_yp'] = $item->first()['vendors_yp'];
                $array['sub_id'] = $item->first()['sub_id'];
                $array['pub_list_id'] = $item->first()['pub_list_id'];
            } else {
                $array['campaign_name_id'] = '';
                $array['vendors_yp'] = '';
                $array['sub_id'] = '';
                $array['pub_list_id'] = '';
            }
            $array['cpl'] = $item->sum('cpl');
            $array['type'] = $item->first()['type'];
            $array['leads'] = $item->sum('leads');
            $array['view_by'] = $item->first()['view_by'];

            return $array;
        })->values();

        $totals_convertions = array_merge($totals_convertions_c, $totals_convertions_s);
        $totals_convertions = new Collection($totals_convertions);

        $total_leads_cpl = $total_leads_cpl->map(function ($item) use ($totals_convertions, $fields) {
            $array = [];
            if ($fields == 'sub_pub') {
                $data = $totals_convertions->where('pub_list_id', $item['pub_list_id'])
                    ->where('sub_id', $item['sub_id']);
            } else {
                $data = $totals_convertions->where($fields, $item[$fields]);
            }
            if ($data->count() > 0) {
                $data = $data->values();
                $array = $data[0];
                $array['cpl'] = $data->sum('cpl') + $item['cpl'];
                $array['cpl_calls'] = $data->sum('cpl');
                $array['cpl_leads'] = $item['cpl'];
                $array['revenue'] = $data->sum('revenue');
                $array['calls'] = $data->sum('calls');
                $array['converted'] = $data->sum('converted');
                $array['answered'] = $data->sum('answered');
                $array['leads'] = $item['leads'];
            } else {
                if ($fields != 'type') {
                    $array['campaign_name_id'] = $item['campaign_name_id'];
                    $array['sub_id'] = $item['sub_id'];
                    $array['pub_list_id'] = $item['pub_list_id'];
                    $array['vendors_yp'] = $item['vendors_yp'];
                } else {
                    $array['campaign_name_id'] = '';
                    $array['sub_id'] = '';
                    $array['pub_list_id'] = '';
                    $array['vendors_yp'] = '';
                }
                $array['vendors_td'] = '';
                $array['view_by'] = $item['view_by'];
                $array['revenue'] = '0.00';
                $array['cpl'] = $item['cpl'];
                $array['cpl_calls'] = 0;
                $array['cpl_leads'] = $item['cpl'];
                $array['calls'] = '0';
                $array['converted'] = '0';
                $array['answered'] = '0';
                $array['type'] = $item['type'];
                $array['leads'] = $item['leads'];
            }

            return $array;
        })->filter();

        return new PersonalCollection($this->sortCollection($total_leads_cpl));
    }

    public function getPubID($pub_id)
    {
        $pub = new PubRepository();
        $pub = $pub->findById($pub_id);
        if ($pub) {
            return $pub->pub_list_id;
        }

        return 100;
    }
}
