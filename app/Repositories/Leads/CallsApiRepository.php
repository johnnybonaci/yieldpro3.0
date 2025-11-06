<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Convertion;
use App\Http\Resources\Leads\QaResource;
use App\Repositories\EloquentRepository;
use App\Http\Resources\Leads\CpaResource;
use App\Http\Resources\Leads\RpcResource;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Collection as PersonalCollection;

class CallsApiRepository extends EloquentRepository
{
    /**
     * Return Totals Leads from date start & date end.
     */
    public function calls(string $date_start, string $date_end): Builder
    {
        $provider_id = env('TRACKDRIVE_PROVIDER_ID', 2);

        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $buyers = array_filter($buyers);

        $states = request()->input('select_states', []);
        $states = is_array($states) ? $states : explode(',', $states);
        $states = array_filter($states);

        $insurances = request()->input('select_insurances', []);
        $insurances = is_array($insurances) ? $insurances : explode(',', $insurances);
        $insurances = array_filter($insurances);

        $hasCallIssues = request()->input('call_issues');
        $hasCallIssues = is_null($hasCallIssues) ? null : request()->boolean('call_issues');

        $issuesTypes = request()->collect('select_issues_types');

        $phone = request()->input('phone', null);

        $col = ['convertions.id', 'convertions.phone_id', 'convertions.status', 'convertions.revenue', 'convertions.cpl', 'convertions.durations', 'convertions.calls', 'convertions.converted', 'convertions.terminating_phone', 'convertions.did_number_id', 'convertions.offer_id', 'convertions.created_at as date_sale', 'recordings.billable', 'recordings.status as status_t', 'offers.name as offers', 'buyers.name as buyers', 'buyers.id as buyer_id', 'traffic_sources.name as vendors_td', 'traffic_sources.id as traffic_source_id', 'pubs.pub_list_id', 'recordings.url', 'recordings.transcript', 'recordings.multiple', 'recordings.qa_status', 'recordings.qa_td_status', 'recordings.insurance', 'leads.state', 'leads.sub_id5'];

        return Convertion::select($col)
            ->join('leads', 'leads.phone', '=', 'convertions.phone_id')
            ->leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')

            ->leftJoin('buyers', function ($join) use ($provider_id) {
                $join->on('buyers.id', '=', 'convertions.buyer_id')
                    ->where('buyers.provider_id', '=', $provider_id);
            })
            ->leftJoin('traffic_sources', function ($join) use ($provider_id) {
                $join->on('traffic_sources.id', '=', 'convertions.traffic_source_id')
                    ->where('traffic_sources.provider_id', '=', $provider_id);
            })
            ->whereBetween('convertions.date_history', [$date_start, $date_end])
            ->when($phone, function ($query, $phone) {
                return $query->where('convertions.phone_id', $phone);
            })
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.id', '=', "$buyer");
                }
            })
            ->where(function ($query) use ($states) {
                foreach ($states as $state) {
                    $query->orWhere('leads.state', 'LIKE', "%$state%");
                }
            })
            ->where(function ($query) use ($insurances) {
                foreach ($insurances as $insurance) {
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            })
            ->when(!is_null($hasCallIssues), function ($query) use ($hasCallIssues, $issuesTypes) {
                $query->where('recordings.multiple->call_ending_sooner_result', $hasCallIssues);

                if ($hasCallIssues && count($issuesTypes) > 0) {
                    $query->where(function ($query) use ($issuesTypes) {
                        foreach ($issuesTypes as $issue) {
                            $query->orwhereRaw("JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                        }
                    });
                }
            })
            ->filterFields();
    }

    /**
     * returm average widget.
     */
    public function average(string $date_start, string $date_end): array
    {
        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $buyers = array_filter($buyers);

        $states = request()->input('select_states', []);
        $states = is_array($states) ? $states : explode(',', $states);
        $states = array_filter($states);

        $insurances = request()->input('select_insurances', []);
        $insurances = is_array($insurances) ? $insurances : explode(',', $insurances);
        $insurances = array_filter($insurances);

        $hasCallIssues = request()->input('call_issues');
        $hasCallIssues = is_null($hasCallIssues) ? null : request()->boolean('call_issues');

        $issuesTypes = request()->collect('select_issues_types');
        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted,sum(convertions.answered) as answered';
        $out_count = 0;
        $out_cpl = 0;
        $totals_convertions = Convertion::selectRaw($columns)
            ->leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('leads', 'leads.phone', '=', 'convertions.phone_id')
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('buyers', 'buyers.id', '=', 'convertions.buyer_id')
            ->whereBetween('convertions.date_history', [$date_start, $date_end])
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.id', '=', "$buyer");
                }
            })
            ->where(function ($query) use ($states) {
                foreach ($states as $state) {
                    $query->orWhere('leads.state', 'LIKE', "%$state%");
                }
            })
            ->where(function ($query) use ($insurances) {
                foreach ($insurances as $insurance) {
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            })
            ->when(!is_null($hasCallIssues), function ($query) use ($hasCallIssues, $issuesTypes) {
                $query->where('recordings.multiple->call_ending_sooner_result', $hasCallIssues);

                if ($hasCallIssues && count($issuesTypes) > 0) {
                    $query->where(function ($query) use ($issuesTypes) {
                        foreach ($issuesTypes as $issue) {
                            $query->orwhereRaw("JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                        }
                    });
                }
            })
            ->filterFields()->first();
        $leads_sale_in = Convertion::leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('buyers', 'buyers.id', '=', 'convertions.buyer_id')
            ->selectRaw('MAX(leads.cpl) as cpl')
            ->whereBetween('leads.date_history', [$date_start, $date_end])
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.id', '=', "$buyer");
                }
            })
            ->where(function ($query) use ($states) {
                foreach ($states as $state) {
                    $query->orWhere('leads.state', 'LIKE', "%$state%");
                }
            })
            ->where(function ($query) use ($insurances) {
                foreach ($insurances as $insurance) {
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            })
            ->when(!is_null($hasCallIssues), function ($query) use ($hasCallIssues, $issuesTypes) {
                $query->where('recordings.multiple->call_ending_sooner_result', $hasCallIssues);

                if ($hasCallIssues && count($issuesTypes) > 0) {
                    $query->where(function ($query) use ($issuesTypes) {
                        foreach ($issuesTypes as $issue) {
                            $query->orwhereRaw("JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                        }
                    });
                }
            })
            ->filterFields()
            ->groupBy('leads.phone')
            ->get();

        $out_count = $leads_sale_in->count();
        $out_cpl = $leads_sale_in->sum('cpl');

        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        $lead_api_repository = new LeadApiRepository();

        return $lead_api_repository->calculateAverage($totals_convertions, $total_leads);
    }

    /**
     * returm total leads records.
     */
    public function records(string $date_start, string $date_end): int
    {
        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $buyers = array_filter($buyers);

        $states = request()->input('select_states', []);
        $states = is_array($states) ? $states : explode(',', $states);
        $states = array_filter($states);

        $insurances = request()->input('select_insurances', []);
        $insurances = is_array($insurances) ? $insurances : explode(',', $insurances);
        $insurances = array_filter($insurances);

        $hasCallIssues = request()->input('call_issues');
        $hasCallIssues = is_null($hasCallIssues) ? null : request()->boolean('call_issues');

        $issuesTypes = request()->collect('select_issues_types');

        $a = Convertion::selectRaw('count(convertions.id) as calls')
            ->leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('leads', 'leads.phone', '=', 'convertions.phone_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('buyers', 'buyers.id', '=', 'convertions.buyer_id')
            ->whereBetween('convertions.date_history', [$date_start, $date_end])
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.id', '=', "$buyer");
                }
            })
            ->where(function ($query) use ($states) {
                foreach ($states as $state) {
                    $query->orWhere('leads.state', 'LIKE', "%$state%");
                }
            })
            ->where(function ($query) use ($insurances) {
                foreach ($insurances as $insurance) {
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            })
            ->when(!is_null($hasCallIssues), function ($query) use ($hasCallIssues, $issuesTypes) {
                $query->where('recordings.multiple->call_ending_sooner_result', $hasCallIssues);

                if ($hasCallIssues && count($issuesTypes) > 0) {
                    $query->where(function ($query) use ($issuesTypes) {
                        foreach ($issuesTypes as $issue) {
                            $query->orwhereRaw("JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                        }
                    });
                }
            })
            ->filterFields()
            ->first();

        return $a->calls ?? 0;
    }

    public function calculateDiff(string $start, string $end, array $totals): array
    {
        $lead_api_repository = new LeadApiRepository();
        $average = $this->average($start, $end);

        return $lead_api_repository->calculateDiff($start, $end, $totals, true, $average);
    }

    /**
     * Return calculate CPA.
     * @param mixed $time
     */
    public function reportCpa(string $date_start, string $date_end, $time = false): Builder
    {
        $time_field = 'convertions.date_history';
        if ($time && $date_end == now()->format('Y-m-d')) {
            $date_start = $date_start . ' 00:00:00';
            $time_field = 'convertions.created_at';
            $date_end = now()->subHours()->format('Y-m-d H:i:s');
        }
        $provider_id = env('TRACKDRIVE_PROVIDER_ID', 2);
        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $groupByView = $viewBy == 'convertions.buyer_id' ? 'buyers.name' : 'traffic_sources.name';

        return Convertion::selectRaw('MAX(convertions.revenue) AS total_revenue')
            ->selectRaw('SUM(convertions.revenue) AS total_cost')
            ->selectRaw('SUM(convertions.calls) AS total_calls')
            ->selectRaw('SUM(convertions.converted) AS total_billables')
            ->selectRaw('SUM(recordings.billable) AS total_sales')
            ->selectRaw('COUNT(DISTINCT(convertions.phone_id)) AS total_unique')
            ->selectRaw('AVG(convertions.durations) AS durations')
            ->selectRaw($groupByView . ' as buyer_name')
            ->selectRaw('IF(SUM(recordings.billable) = 0, 0, ROUND(SUM(convertions.revenue) / SUM(recordings.billable),2)) AS total_cpa')
            ->selectRaw('IF(COUNT(DISTINCT(convertions.phone_id)) = 0, 0, ROUND(SUM(convertions.converted) / COUNT(DISTINCT(convertions.phone_id)) * 100,2)) AS total_ucr')
            ->selectRaw('IF(SUM(convertions.answered) = 0, 0, ROUND(SUM(convertions.cpl) / SUM(convertions.answered))) AS total_cpc')
            ->selectRaw('leads.state as state')
            ->join('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('buyers', function ($join) use ($provider_id) {
                $join->on('buyers.id', '=', 'convertions.buyer_id')
                    ->where('buyers.provider_id', '=', $provider_id);
            })
            ->join('leads', function ($join) use ($date_start, $date_end, $time_field) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween($time_field, [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->whereBetween($time_field, [$date_start, $date_end])
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.name', 'LIKE', "%$buyer%");
                }
            })
            ->filterFields();
    }

    /**
     * Return calculate CPA.
     * @param mixed $time
     */
    public function reportRpc(string $date_start, string $date_end, $time = false): Builder
    {
        $time_field = 'convertions.date_history';

        if ($time && $date_end == now()->format('Y-m-d')) {
            $date_start = $date_start . ' 00:00:00';
            $time_field = 'convertions.created_at';
            $date_end = now()->subHours()->format('Y-m-d H:i:s');
        }

        $provider_id = env('TRACKDRIVE_PROVIDER_ID', 2);
        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $groupByView = $viewBy == 'convertions.buyer_id' ? 'buyers.name' : 'traffic_sources.name';

        return Convertion::selectRaw('MAX(buyers.revenue) AS total_revenue')
            ->selectRaw('SUM(convertions.cpl) AS total_cpl')
            ->selectRaw('SUM(CASE WHEN convertions.status = "billable" THEN buyers.revenue ELSE 0 END) AS total_revs')
            ->selectRaw('COUNT(convertions.phone_id) AS total_calls')
            ->selectRaw('COUNT(DISTINCT(convertions.phone_id)) AS total_unique')
            ->selectRaw('AVG(convertions.durations) AS durations')
            ->selectRaw($groupByView . ' as buyer_name')
            ->selectRaw('leads.state as state')
            ->selectRaw('SUM(CASE WHEN convertions.status = "billable" THEN 1 ELSE 0 END) AS total_billables')
            ->selectRaw('IF(COUNT(DISTINCT(convertions.phone_id)) = 0, 0, ROUND((SUM(CASE WHEN convertions.status = "billable" THEN 1 ELSE 0 END) * MAX(buyers.revenue)) / COUNT(DISTINCT(convertions.phone_id)), 2)) AS total_rpc')

            ->join('buyers', function ($join) use ($provider_id) {
                $join->on('buyers.id', '=', 'convertions.buyer_id')
                    ->where('buyers.provider_id', '=', $provider_id);
            })
            ->join('leads', function ($join) use ($date_start, $date_end, $time_field) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween($time_field, [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'pubs.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->whereBetween($time_field, [$date_start, $date_end])
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyers.name', 'LIKE', "%$buyer%");
                }
            })
            ->filterFields();
    }

    /**
     * Return calculate CPA.
     */
    public function reportQa(string $date_start, string $date_end): Builder
    {
        $calls = $this->calls($date_start, $date_end);
        $calls = $calls->where('recordings.billable', 0)->whereNotNull('recordings.qa_status')->whereNotNull('recordings.qa_td_status');

        return $calls;
    }

    public function getWidgetsQa(PersonalCollection $collections): array
    {
        $total = $collections->count();

        return [
            'widgets' => [
                'total_calls' => $total,
                'total_reached_agent' => $total > 0 ? round(($collections->where('reached_agent', true)->count() / $total) * 100, 2) : 0,
                'total_reached_agent_q' => $collections->where('reached_agent', true)->count(),
                'total_ivr' => $total > 0 ? round(($collections->where('ivr', true)->count() / $total) * 100, 2) : 0,
                'total_ivr_q' => $collections->where('ivr', true)->count(),
                'total_avg_hold_durations' => __toMinutes(intval($collections->avg('o_hold_durations'))),
                'total_ten_hold_durations_q' => $collections->where('o_hold_durations', '>', 10)->count(),
                'total_ten_hold_durations' => $total > 0 ? round(($collections->where('o_hold_durations', '>', 10)->count() / $total) * 100, 2) : 0,
                'total_avg_durations' => __toMinutes(intval($collections->avg('o_durations'))),
                'total_ad_quality_error' => $total > 0 ? round(($collections->where('ad_quality_error', true)->count() / $total) * 100, 2) : 0,
                'total_call_dropped' => $total > 0 ? round(($collections->where('call_dropped', true)->count() / $total) * 100, 2) : 0,
                'total_call_dropped_q' => $collections->where('call_dropped', true)->count(),
                'total_not_interested' => $total > 0 ? round(($collections->where('not_interested', true)->count() / $total) * 100, 2) : 0,
                'total_not_interested_q' => $collections->where('not_interested', true)->count(),
                'total_not_qualified' => $total > 0 ? round(($collections->where('not_qualified', true)->count() / $total) * 100, 2) : 0,
                'total_not_qualified_q' => $collections->where('not_qualified', true)->count(),
                'total_caller_hung_up' => $total > 0 ? round(($collections->where('caller_hung_up', true)->count() / $total) * 100, 2) : 0,
                'total_caller_hung_up_q' => $collections->where('caller_hung_up', true)->count(),
            ],
        ];
    }

    public function getWidgetsCpa(Builder $convertion): array
    {
        $total = $convertion->first()->toArray();

        return ['widgets' => CpaResource::make($total)->toArray(request())];
    }

    public function getWidgetsRpc(Builder $convertion): array
    {
        $total = $convertion->first()->toArray();

        return ['widgets' => RpcResource::make($total)->toArray(request())];
    }

    public function sortCpaCollections(string $date_start, string $date_end): PersonalCollection
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $sort = request()->input('sort', [['field' => 'total_sales', 'dir' => 'desc']]);
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;
        $report = $this->reportCpa($date_start, $date_end);
        $byHour = $this->cpaByHourleft($date_start, $date_end, $viewBy);
        $groupBy = [$viewBy, 'leads.state'];
        $list = $report->groupBy($groupBy);
        $list = $list->get()->collect();
        $list = $list->groupBy('buyer_name')->mapWithKeys(function ($item, $key) {
            return [$key => [
                'total_revenue' => $item->first()->total_revenue,
                'total_cost' => $item->sum('total_cost'),
                'total_calls' => 0,
                'total_billables' => $item->sum('total_billables'),
                'total_sales' => $item->sum('total_sales'),
                'durations' => 0,
                'total_cpa' => $item->sum('total_sales') > 0 ? round($item->sum('total_cost') / $item->sum('total_sales'), 2) : 0,
                'total_ucr' => $item->sum('total_unique') > 0 ? round($item->sum('total_billables') / $item->sum('total_unique') * 100, 2) : 0,
                'total_unique' => $item->sum('total_unique'),
                '_children' => $item->toArray(),
                'buyer_name' => $key,
            ]];
        });
        $final = $list->mapWithKeys(function ($item, $key) use ($byHour) {
            $item['total_ucr_1'] = 'Up';
            if (array_key_exists($key, $byHour)) {
                $item['total_ucr_1'] = match (true) {
                    $item['total_ucr'] > $byHour[$key]['total_ucr_1'] => 'Up',
                    $item['total_ucr'] < $byHour[$key]['total_ucr_1'] => 'Down',
                    default => 'Same',
                };
            }
            foreach ($item['_children'] as $keychild => $value) {
                $value['total_ucr_1'] = 0;
                $children[$keychild] = $value;
            }
            $item['_children'] = $children;

            return [$key => $item];
        });

        return new PersonalCollection($final->sortBy($fields, SORT_REGULAR, $dir)->values());
    }

    public function sortRpcCollections(string $date_start, string $date_end): PersonalCollection
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $sort = request()->input('sort', [['field' => 'total_sales', 'dir' => 'desc']]);
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;
        $report = $this->reportRpc($date_start, $date_end);
        $byHour = $this->cpaByHourleft($date_start, $date_end, $viewBy);
        $groupBy = [$viewBy, 'leads.state'];
        $list = $report->groupBy($groupBy);
        $list = $list->get()->collect();
        $list = $list->groupBy('buyer_name')->mapWithKeys(function ($item, $key) {
            $unique = $item->sum('total_unique');
            $billables = $item->sum('total_billables');
            $revenue = $item->first()->total_revenue;

            return [$key => [
                'total_revenue' => $revenue,
                'total_revs' => $item->sum('total_revs'),
                'total_calls' => $item->sum('total_calls'),
                'total_unique' => $unique,
                'durations' => $item->avg('durations'),
                'buyer_name' => $key,
                'state' => '',
                'total_billables' => $billables,
                'total_rpc' => $unique > 0 ? round(($billables * $revenue) / $unique, 2) : 0,
                '_children' => $item->toArray(),
            ]];
        });

        return new PersonalCollection($list->sortBy($fields, SORT_REGULAR, $dir)->values());
    }

    public function cpaByHourleft($date_start, $date_end, $viewBy): array
    {
        $report = $this->reportCpa($date_start, $date_end, true);

        $groupBy = [$viewBy, 'leads.state'];
        $list = $report->groupBy($groupBy);
        $list = $list->get()->collect();
        $list = $list->groupBy('buyer_name')->mapWithKeys(function ($item, $key) {
            $total = $item->map(function ($data) {
                $datos['total_ucr_1'] = $data->total_ucr;

                return $datos;
            })->toArray();

            return [$key => [
                'total_ucr_1' => $item->sum('total_unique') > 0 ? round($item->sum('total_billables') / $item->sum('total_unique') * 100, 2) : 0,
                '_children' => $total,
            ]];
        });

        return $list->toArray();
    }

    public function sortQaCollections($list): PersonalCollection
    {
        return new PersonalCollection($list->get());
    }

    public function qaReportCollect(): array
    {
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $report = $this->sortQaCollections($this->reportQa($date_start, $date_end));
        $resources = QaResource::collection($report);
        $collections = $resources->map(function ($item) {
            return $item->toArray(request());
        });
        $widgets = $this->getWidgetsQa($collections);

        return [
            $widgets,
            $report,
            $collections,
        ];
    }
}
