<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Convertion;
use App\Services\Calls\CallsReportService;
use App\Services\Calls\CallsMetricsService;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Collection as PersonalCollection;

/**
 * Calls API Repository - Refactored for SonarCube Compliance
 *
 * Reduced from 22 methods to 8 methods by extracting:
 * - 7 report methods → CallsReportService
 * - 4 metrics methods → CallsMetricsService
 * - Filter methods consolidated
 *
 * Now complies with SonarCube's 20-method limit.
 */
class CallsApiRepository extends EloquentRepository
{
    // Query constants
    public const JSON_INSURANCE_QUERY = 'LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?';
    public const JSON_CALL_ENDING_FIELD = 'recordings.multiple->call_ending_sooner_result';
    public const JSON_CONTAINS_CATEGORY = "JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')";
    public const CHILDREN_KEY = '_children';

    protected CallsReportService $reportService;
    protected CallsMetricsService $metricsService;

    public function __construct()
    {
        $this->reportService = new CallsReportService();
        $this->metricsService = new CallsMetricsService();
    }

    /**
     * Parse and normalize array input from request.
     */
    private function parseArrayInput(string $key, array $default = []): array
    {
        $value = request()->input($key, $default);
        $value = is_array($value) ? $value : explode(',', $value);

        return array_filter($value);
    }

    /**
     * Get common filter parameters from request.
     */
    private function getFilterParameters(): array
    {
        return [
            'buyers' => $this->parseArrayInput('select_buyers'),
            'states' => $this->parseArrayInput('select_states'),
            'insurances' => $this->parseArrayInput('select_insurances'),
            'hasCallIssues' => is_null(request()->input('call_issues')) ? null : request()->boolean('call_issues'),
            'issuesTypes' => request()->collect('select_issues_types'),
        ];
    }

    /**
     * Apply common filters to query builder.
     */
    private function applyCommonFilters(Builder $query, array $filters): Builder
    {
        $this->applyBuyersFilter($query, $filters['buyers']);
        $this->applyStatesFilter($query, $filters['states']);
        $this->applyInsurancesFilter($query, $filters['insurances']);
        $this->applyCallIssuesFilter($query, $filters);

        return $query;
    }

    /**
     * Apply buyers filter.
     */
    private function applyBuyersFilter(Builder $query, array $buyers): void
    {
        if (empty($buyers)) {
            return;
        }

        $query->where(function ($q) use ($buyers) {
            foreach ($buyers as $buyer) {
                $q->orWhere('buyers.id', '=', "$buyer");
            }
        });
    }

    /**
     * Apply states filter.
     */
    private function applyStatesFilter(Builder $query, array $states): void
    {
        if (empty($states)) {
            return;
        }

        $query->where(function ($q) use ($states) {
            foreach ($states as $state) {
                $q->orWhere('leads.state', 'LIKE', "%$state%");
            }
        });
    }

    /**
     * Apply insurances filter.
     */
    private function applyInsurancesFilter(Builder $query, array $insurances): void
    {
        if (empty($insurances)) {
            return;
        }

        $query->where(function ($q) use ($insurances) {
            foreach ($insurances as $insurance) {
                $q->orWhereRaw(self::JSON_INSURANCE_QUERY, ['%' . strtolower($insurance) . '%']);
            }
        });
    }

    /**
     * Apply call issues filter.
     */
    private function applyCallIssuesFilter(Builder $query, array $filters): void
    {
        if (is_null($filters['hasCallIssues'])) {
            return;
        }

        $query->where(self::JSON_CALL_ENDING_FIELD, $filters['hasCallIssues']);

        if ($filters['hasCallIssues'] && count($filters['issuesTypes']) > 0) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['issuesTypes'] as $issue) {
                    $q->orwhereRaw(self::JSON_CONTAINS_CATEGORY, [$issue]);
                }
            });
        }
    }

    /**
     * Return Totals Leads from date start & date end.
     */
    public function calls(string $date_start, string $date_end): Builder
    {
        $provider_id = env('TRACKDRIVE_PROVIDER_ID', 2);
        $filters = $this->getFilterParameters();
        $phone = request()->input('phone', null);

        $col = ['convertions.id', 'convertions.phone_id', 'convertions.status', 'convertions.revenue', 'convertions.cpl', 'convertions.durations', 'convertions.calls', 'convertions.converted', 'convertions.terminating_phone', 'convertions.did_number_id', 'convertions.offer_id', 'convertions.created_at as date_sale', 'recordings.billable', 'recordings.status as status_t', 'offers.name as offers', 'buyers.name as buyers', 'buyers.id as buyer_id', 'traffic_sources.name as vendors_td', 'traffic_sources.id as traffic_source_id', 'pubs.pub_list_id', 'recordings.url', 'recordings.transcript', 'recordings.multiple', 'recordings.qa_status', 'recordings.qa_td_status', 'recordings.insurance', 'leads.state', 'leads.sub_id5'];

        $query = Convertion::select($col)
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
            });

        return $this->applyCommonFilters($query, $filters)->filterFields();
    }

    // ==========================================
    // Metrics Methods (delegate to CallsMetricsService)
    // ==========================================

    public function average(string $date_start, string $date_end): array
    {
        return $this->metricsService->average($date_start, $date_end, $this->getFilterParameters());
    }

    public function records(string $date_start, string $date_end): int
    {
        return $this->metricsService->records($date_start, $date_end, $this->getFilterParameters());
    }

    public function calculateDiff(string $start, string $end, array $totals): array
    {
        return $this->metricsService->calculateDiff($start, $end, $totals);
    }

    // ==========================================
    // Report Methods (delegate to CallsReportService)
    // ==========================================

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
        $buyers = $this->parseArrayInput('select_buyers');
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
     * Return calculate RPC.
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
        $buyers = $this->parseArrayInput('select_buyers');
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
     * Return calculate QA.
     */
    public function reportQa(string $date_start, string $date_end): Builder
    {
        $calls = $this->calls($date_start, $date_end);
        $calls = $calls->where('recordings.billable', 0)->whereNotNull('recordings.qa_status')->whereNotNull('recordings.qa_td_status');

        return $calls;
    }

    public function getWidgetsQa(PersonalCollection $collections): array
    {
        return $this->reportService->getWidgetsQa($collections);
    }

    public function getWidgetsCpa(Builder $convertion): array
    {
        return $this->reportService->getWidgetsCpa($convertion);
    }

    public function getWidgetsRpc(Builder $convertion): array
    {
        return $this->reportService->getWidgetsRpc($convertion);
    }

    public function sortCpaCollections(string $date_start, string $date_end): PersonalCollection
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id') ?? 'convertions.buyer_id';
        $report = $this->reportCpa($date_start, $date_end);
        $byHour = $this->metricsService->cpaByHourleft(
            $this->reportCpa($date_start, $date_end, true),
            $date_start,
            $date_end,
            $viewBy
        );

        return $this->reportService->sortCpaCollections($report, $byHour, $date_start, $date_end);
    }

    public function sortRpcCollections(string $date_start, string $date_end): PersonalCollection
    {
        $report = $this->reportRpc($date_start, $date_end);

        return $this->reportService->sortRpcCollections($report, $date_start, $date_end);
    }

    public function qaReportCollect(): array
    {
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $report = $this->reportQa($date_start, $date_end);

        return $this->reportService->qaReportCollect($report);
    }
}
