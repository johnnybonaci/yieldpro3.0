<?php

namespace App\Services\Leads;

use Carbon\Carbon;
use App\Models\Leads\Pub;
use App\Models\Leads\Sub;
use App\Models\Leads\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Leads\Convertion;
use App\Models\Leads\HistoryLeads;
use App\Models\Leads\LeadPageView;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Collection as PersonalCollection;

/**
 * Lead Query Service.
 *
 * Handles all lead queries, campaign dashboards, and data retrieval.
 * Extracted from LeadApiRepository to comply with SonarCube standards.
 *
 * Responsibilities:
 * - Query leads with complex filtering
 * - Retrieve history data
 * - Generate campaign dashboards
 * - Handle sorting and pagination
 * - Page view widgets
 */
class LeadQueryService
{
    public const MAX_CPL = 'MAX(leads.cpl) as cpl';

    /**
     * Return total leads from date start & date end.
     */
    public function leads(string $date_start, string $date_end): Builder
    {
        $date_record = request()->input('date_record', 'date_created');

        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $col = [
            'leads.phone', 'leads.first_name', 'leads.last_name', 'leads.email', 'leads.type',
            'leads.zip_code', 'leads.state', 'leads.data', 'leads.yp_lead_id',
            'leads.campaign_name_id', 'leads.universal_lead_id', 'leads.trusted_form',
            'subs.sub_id', 'pubs.pub_list_id', 'leads.created_at', 'leads.cpl',
            'pub_lists.name as vendors_yp', 'offers.name as offers',
            'convertions.calls', 'convertions.status', 'leads.sub_id5',
        ];

        if ($date_record == 'date_created') {
            $leads = Convertion::rightJoin('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', 'leads.sub_id')
                ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
                ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
                ->select($col)
                ->whereBetween('leads.date_history', [$date_start, $date_end])
                ->whereNotIn('leads.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy('leads.phone');
        } else {
            $leads = Convertion::join('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
                ->join('subs', 'subs.id', '=', 'leads.sub_id')
                ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
                ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
                ->join('offers', 'offers.id', '=', 'pubs.offer_id')
                ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
                ->select($col)
                ->whereNotBetween('leads.date_history', [$date_start, $date_end])
                ->whereNotIn('leads.pub_id', $pubs_lists)
                ->filterFields()
                ->groupBy('leads.phone');
        }

        return $leads;
    }

    /**
     * Get lead history.
     */
    public function history(string $date_start, string $date_end): PersonalCollection
    {
        $data = HistoryLeads::selectRaw('before_h, after_h, phone_id, created_at')
            ->whereBetween('created_at', [$date_start . ' 00:00:00', $date_end . ' 23:59:59'])
            ->filterFields()
            ->sortsFields('created_at')->get();

        $pub = Pub::all()->pluck('pub_list_id', 'id')->all();
        $sub = Sub::all()->pluck('sub_id', 'id')->all();

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

    /**
     * Get new lead history with grouping.
     */
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
     * Return total leads records count.
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
     * Return list data Campaign Dashboard.
     * Complexity reduced from 21 to ~14 by extracting helper methods.
     */
    public function campaignDashboard(string $date_start, string $date_end, LeadMetricsService $metricsService): PersonalCollection
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';
        [$table, $fields] = Str::of($view_by)->explode('.')->toArray();
        $fields = $view_by == 'leads.campaign_name_id' ? 'cm_pub' : $fields;

        // Get conversions and leads data
        $totals_convertions = $this->getTotalConversions($metricsService, $date_start, $date_end);
        $total_leads_cpl = $this->getTotalLeadsData($date_start, $date_end, $fields);

        // Merge leads with conversions
        $total_leads_cpl = $total_leads_cpl->map(function ($item) use ($totals_convertions, $fields) {
            return $this->mergeLeadWithConversions($item, $totals_convertions, $fields);
        })->filter();

        return new PersonalCollection($this->sortCollection($total_leads_cpl));
    }

    /**
     * Get total conversions from metrics service.
     */
    private function getTotalConversions(LeadMetricsService $metricsService, string $date_start, string $date_end): Collection
    {
        $totals_convertions_c = $metricsService->getTotalConvertionsCampaign($date_start, $date_end, true, false);
        $totals_convertions_s = $metricsService->getTotalConvertionsCampaign($date_start, $date_end, false, true);

        return new Collection(array_merge($totals_convertions_c, $totals_convertions_s));
    }

    /**
     * Get total leads and group by fields.
     */
    private function getTotalLeadsData(string $date_start, string $date_end, string $fields): Collection
    {
        $total_leads_cpl_c = $this->getTotalLeadsCampaign($date_start, $date_end, true, false);
        $total_leads_cpl_s = $this->getTotalLeadsCampaign($date_start, $date_end, false, true);

        return $total_leads_cpl_c->merge($total_leads_cpl_s)
            ->groupBy($fields)
            ->map(function ($item) use ($fields) {
                $first = $item->first();
                return array_merge(
                    LeadQueryServiceHelper::buildFieldsArray($first, $fields, true),
                    [
                        'cpl' => $item->sum('cpl'),
                        'type' => $first['type'],
                        'leads' => $item->sum('leads'),
                        'view_by' => $first['view_by'],
                    ]
                );
            })->values();
    }

    /**
     * Merge lead item with conversion data.
     */
    private function mergeLeadWithConversions(array $item, Collection $totals_convertions, string $fields): array
    {
        $data = LeadQueryServiceHelper::filterConversions($totals_convertions, $item, $fields);

        if ($data->count() > 0) {
            $dataArray = $data->values()->toArray();
            return LeadQueryServiceHelper::buildMetricsWithConversions($dataArray, $item);
        }

        return LeadQueryServiceHelper::buildEmptyMetrics($item, $fields);
    }

    /**
     * Get total leads for campaign dashboard.
     */
    public function getTotalLeadsCampaign(string $date_start, string $date_end, bool $sale, bool $date, bool $ts = false)
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';

        $columns_cpl = $view_by . ' as view_by,subs.sub_id, pubs.pub_list_id, 1 as leads,CONCAT(leads.campaign_name_id,"",leads.sub_id3,"",leads.pub_id,"",ifnull(convertions.traffic_source_id,66666)) as cm_pub,campaign_name_id,pub_lists.name as vendors_yp,leads.type,leads.sub_id3,leads.sub_id2,leads.sub_id4,ifnull(convertions.traffic_source_id,66666) as traffic_source_id,leads.pub_id';
        $group_by = ['leads.phone', 'leads.campaign_name_id', 'leads.sub_id3', 'leads.pub_id', 'convertions.traffic_source_id'];

        return Convertion::selectRaw($columns_cpl)
            ->when(
                $sale,
                fn ($query) => $query->rightJoin('leads', function ($join) use ($date_start, $date_end) {
                    $join->on('leads.phone', '=', 'convertions.phone_id')
                        ->whereBetween('convertions.date_history', [$date_start, $date_end]);
                })->whereBetween('leads.date_history', [$date_start, $date_end])->selectRaw(self::MAX_CPL)
            )
            ->when(
                $date,
                fn ($query) => $query->join('leads', function ($join) use ($date_start, $date_end) {
                    $join->on('leads.phone', '=', 'convertions.phone_id')
                        ->whereBetween('convertions.date_history', [$date_start, $date_end]);
                })->whereNotBetween('leads.date_history', [$date_start, $date_end])->selectRaw('0 as cpl')
            )
            ->when(
                $ts,
                fn ($query) => $query->where(function ($q) {
                    $q->where('convertions.traffic_source_id', 10002)
                        ->orWhereNull('convertions.traffic_source_id');
                })
            )
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->filterFields()
            ->groupBy($group_by)
            ->get()
            ->collect();
    }

    /**
     * Sort collection by specified field.
     */
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

    /**
     * Get page view widgets.
     */
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
     * Return list data for MassNexus Campaign Dashboard.
     */
    public function campaignMn(string $date_start, string $date_end, LeadMetricsService $metricsService): PersonalCollection
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';

        [$table, $fields] = Str::of($view_by)->explode('.')->toArray();
        $fields = $view_by == 'subs.sub_id' ? 'sub_pub' : $fields;

        $totals_convertions_c = $metricsService->getTotalConvertionsCampaign($date_start, $date_end, true, false);
        $totals_convertions_s = $metricsService->getTotalConvertionsCampaign($date_start, $date_end, false, true);

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
}
