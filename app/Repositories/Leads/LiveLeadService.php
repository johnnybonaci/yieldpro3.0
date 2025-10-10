<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Call;
use App\Models\Leads\LiveLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;

class LiveLeadService
{
    public function leadsQuery(string $date_start, string $date_end): Builder
    {
        $request = request();

        return LiveLead::query()
            ->with(['latestCall' => function ($query) use ($date_start, $date_end) {
                $query->whereBetween('td_created_at_date', [$date_start, $date_end]);
            }])
            ->leftJoinSub(
                DB::table('calls')
                    ->select('status', 'traffic_source_name', 'phone_id')
                    ->whereBetween('td_created_at_date', [$date_start, $date_end])
                    ->groupBy('phone_id'),
                'latest_calls',
                function (JoinClause $join) {
                    $join->on('live_leads.phone', '=', 'latest_calls.phone_id');
                }
            )
            ->whereNotNull(['sub_id', 'pub_id', 'pub_offer_id', 'publisher_id'])
            ->when($request->filled('phone'), function ($query) use ($request) {
                $query->where('phone', $request->get('phone'));
            })
            ->when($request->filled('first_name'), function ($query) use ($request) {
                $query->whereLike('first_name', $request->get('phone'));
            })
            ->when($request->filled('leads_type'), function ($query) use ($request) {
                $types = explode(',', $request->get('leads_type', ''));

                $query->whereIn('type', $types);
            })
            ->when($request->filled('pubs_pub1list1id'), function ($query) use ($request) {
                $values = $request->get('pubs_pub1list1id', []);

                $query->whereIn('publisher_id', $values);
            })
            ->when($request->filled('leads_sub1id5'), function ($query) {
                $values = request()->input('leads_sub1id5', []);

                $query->whereIn('sub_id5', $values);
            })
            ->when($request->filled('campaign1name1id'), function ($query) use ($request) {
                $query->where('campaign_name_id', $request->get('campaign1name1id'));
            })
            ->when($request->filled('convertions_traffic1source1id'), function ($query) use ($request) {
                $values = $request->get('convertions_traffic1source1id', []);

                $query->whereIn('traffic_source_name', $values);
            })
            ->when($request->filled('convertions_status'), function ($query) use ($request) {
                $value = $request->get('convertions_status');

                $query->where('status', $value);
            })
            ->when($request->filled('filter'), function ($query) use ($request) {
                $filters = $request->get('filter', []);

                foreach ($filters as $filter) {
                    $value = $filter['type'] === 'like' ? '%' . $filter['value'] . '%' : $filter['value'];

                    $query->where($filter['field'], $filter['type'], $value);
                }
            })
            ->whereBetween('created_at_date', [$date_start, $date_end])
            ->whereNotIn('pub_id', [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70])
            ->orderByDesc('created_at');
    }

    public function leadsCursor(string $date_start, string $date_end): LazyCollection
    {
        return $this->leadsQuery($date_start, $date_end)->cursor();
    }

    public function paginate(string $date_start, string $date_end): LengthAwarePaginator
    {
        $request = request();

        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        return $this->leadsQuery($date_start, $date_end)
            ->paginate($size, ['*'], 'page', $page);
    }

    public function average(string $date_start, string $date_end): array
    {
        $request = request();
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70];

        $totalConvertions = Call::query()
            ->selectRaw('sum(calls.revenue) as revenue,sum(calls.cpl) as cpl,sum(calls.calls) as calls,sum(calls.converted) as converted, sum(calls.answered) as answered,count(DISTINCT(calls.phone_id)) as unique_calls')
            ->whereBetween('td_created_at_date', [$date_start, $date_end])
            ->whereBetween('lead_created_at_date', [$date_start, $date_end])
            ->whereNotIn('lead_pub_id', $pubs_lists)
            ->when($request->filled('leads_type'), function ($query) use ($request) {
                $types = explode(',', $request->get('leads_type', ''));

                $query->whereIn('lead_type', $types);
            })
            ->when($request->filled('pubs_pub1list1id'), function ($query) use ($request) {
                $values = $request->get('pubs_pub1list1id', []);

                $query->whereIn('lead_publisher_id', $values);
            })
            ->when($request->filled('campaign1name1id'), function ($query) use ($request) {
                $query->where('lead_campaign_name_id', $request->get('campaign1name1id'));
            })
            ->when($request->filled('convertions_traffic1source1id'), function ($query) use ($request) {
                $values = $request->get('convertions_traffic1source1id', []);

                $query->whereIn('traffic_source_name', $values);
            })
            ->when($request->filled('convertions_status'), function ($query) use ($request) {
                $query->where('status', $request->get('convertions_status'));
            })
            ->when($request->filled('filter'), function ($query) use ($request) {
                $filters = $request->collect('filter', []);

                foreach ($filters as $filter) {
                    $field = match ($filter['field']) {
                        'phone' => 'phone_id',
                        'first_name' => 'lead_first_name',
                        'last_name' => 'lead_last_name',
                        'email' => 'lead_email',
                        default => $filter,
                    };

                    $value = $filter['type'] === 'like' ? '%' . $filter['value'] . '%' : $filter['value'];

                    $query->where($field, $filter['type'], $value);
                }
            })
            ->limit(1)
            ->first();

        $baseLiveLeadQuery = $this->leadsQuery($date_start, $date_end)
            ->select(DB::raw('SUM(cpl) as cpl'), DB::raw('count(*) as leads'))
            ->first();

        $total_leads = (object) ['leads' => $baseLiveLeadQuery->leads, 'cpl' => $baseLiveLeadQuery->cpl];

        return $this->calculateAverage($totalConvertions, $total_leads);
    }

    public function calculateDiff(string $start, string $end, array $totals): array
    {
        $avg = 'average';

        $average = $this->average($start, $end);

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

    public function calculateDiffCalls(string $start, string $end, array $totals, array $averageCalls): array
    {
        $avg = 'average';

        $average = $averageCalls;

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

    protected function setAverage(array $var, string $name, ?float $leads_cpl, ?float $convertions_cpl): array
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
}
