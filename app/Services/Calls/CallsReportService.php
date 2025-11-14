<?php

namespace App\Services\Calls;

use Illuminate\Database\Eloquent\Builder;
use App\Support\Collection as PersonalCollection;
use App\Http\Resources\Leads\CpaResource;
use App\Http\Resources\Leads\RpcResource;
use App\Http\Resources\Leads\QaResource;

/**
 * Calls Report Service
 *
 * Handles all call reporting functionality (CPA, RPC, QA).
 * Extracted from CallsApiRepository to comply with SonarCube standards.
 */
class CallsReportService
{
    public const CHILDREN_KEY = '_children';

    /**
     * Get widgets for CPA report.
     */
    public function getWidgetsCpa(Builder $convertion): array
    {
        $total = $convertion->first()->toArray();

        return ['widgets' => CpaResource::make($total)->toArray(request())];
    }

    /**
     * Get widgets for RPC report.
     */
    public function getWidgetsRpc(Builder $convertion): array
    {
        $total = $convertion->first()->toArray();

        return ['widgets' => RpcResource::make($total)->toArray(request())];
    }

    /**
     * Get widgets for QA report.
     */
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

    /**
     * Sort CPA collections with grouping and calculations.
     */
    public function sortCpaCollections(Builder $reportCpa, array $byHourData, string $date_start, string $date_end): PersonalCollection
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $sort = request()->input('sort', [['field' => 'total_sales', 'dir' => 'desc']]);
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;
        $groupBy = [$viewBy, 'leads.state'];

        $list = $reportCpa->groupBy($groupBy);
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
                self::CHILDREN_KEY => $item->toArray(),
                'buyer_name' => $key,
            ]];
        });

        $final = $list->mapWithKeys(function ($item, $key) use ($byHourData) {
            $item['total_ucr_1'] = 'Up';
            if (array_key_exists($key, $byHourData)) {
                $item['total_ucr_1'] = match (true) {
                    $item['total_ucr'] > $byHourData[$key]['total_ucr_1'] => 'Up',
                    $item['total_ucr'] < $byHourData[$key]['total_ucr_1'] => 'Down',
                    default => 'Same',
                };
            }
            foreach ($item[self::CHILDREN_KEY] as $keychild => $value) {
                $value['total_ucr_1'] = 0;
                $children[$keychild] = $value;
            }
            $item[self::CHILDREN_KEY] = $children;

            return [$key => $item];
        });

        return new PersonalCollection($final->sortBy($fields, SORT_REGULAR, $dir)->values());
    }

    /**
     * Sort RPC collections with grouping and calculations.
     */
    public function sortRpcCollections(Builder $reportRpc, string $date_start, string $date_end): PersonalCollection
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $sort = request()->input('sort', [['field' => 'total_sales', 'dir' => 'desc']]);
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;
        $groupBy = [$viewBy, 'leads.state'];

        $list = $reportRpc->groupBy($groupBy);
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
                self::CHILDREN_KEY => $item->toArray(),
            ]];
        });

        return new PersonalCollection($list->sortBy($fields, SORT_REGULAR, $dir)->values());
    }

    /**
     * Sort QA collections.
     */
    public function sortQaCollections($list): PersonalCollection
    {
        return new PersonalCollection($list->get());
    }

    /**
     * Collect QA report data with widgets and resources.
     */
    public function qaReportCollect(Builder $reportQa): array
    {
        $report = $this->sortQaCollections($reportQa);
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
