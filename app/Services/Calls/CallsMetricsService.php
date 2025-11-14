<?php

namespace App\Services\Calls;

use App\Models\Leads\Convertion;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\Leads\LeadApiRepository;

/**
 * Calls Metrics Service
 *
 * Handles call metrics calculations and averages.
 * Extracted from CallsApiRepository to comply with SonarCube standards.
 */
class CallsMetricsService
{
    /**
     * Calculate average metrics for calls.
     */
    public function average(string $date_start, string $date_end, array $filters): array
    {
        $columns = 'sum(convertions.revenue) as revenue,sum(convertions.cpl) as cpl,sum(convertions.calls) as calls,sum(convertions.converted) as converted,sum(convertions.answered) as answered';
        $out_count = 0;
        $out_cpl = 0;

        $query = Convertion::selectRaw($columns)
            ->leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('leads', 'leads.phone', '=', 'convertions.phone_id')
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('buyers', 'buyers.id', '=', 'convertions.buyer_id')
            ->whereBetween('convertions.date_history', [$date_start, $date_end]);

        $totals_convertions = $this->applyFiltersToQuery($query, $filters)->filterFields()->first();

        $leadsQuery = Convertion::leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
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
            ->whereBetween('leads.date_history', [$date_start, $date_end]);

        $leads_sale_in = $this->applyFiltersToQuery($leadsQuery, $filters)
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
     * Get total records count for calls.
     */
    public function records(string $date_start, string $date_end, array $filters): int
    {
        $query = Convertion::selectRaw('count(convertions.id) as calls')
            ->leftJoin('recordings', 'recordings.id', '=', 'convertions.id')
            ->join('leads', 'leads.phone', '=', 'convertions.phone_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('buyers', 'buyers.id', '=', 'convertions.buyer_id')
            ->whereBetween('convertions.date_history', [$date_start, $date_end]);

        $a = $this->applyFiltersToQuery($query, $filters)
            ->filterFields()
            ->first();

        return $a->calls ?? 0;
    }

    /**
     * Calculate difference metrics between periods.
     */
    public function calculateDiff(string $start, string $end, array $totals): array
    {
        $lead_api_repository = new LeadApiRepository();
        $average = $this->average($start, $end, $this->getFilterParameters());

        return $lead_api_repository->calculateDiff($start, $end, $totals, true, $average);
    }

    /**
     * Calculate CPA by hour (left period).
     */
    public function cpaByHourleft(Builder $reportCpa, string $date_start, string $date_end, string $viewBy): array
    {
        $groupBy = [$viewBy, 'leads.state'];
        $list = $reportCpa->groupBy($groupBy);
        $list = $list->get()->collect();
        $list = $list->groupBy('buyer_name')->mapWithKeys(function ($item, $key) {
            $total = $item->map(function ($data) {
                $datos['total_ucr_1'] = $data->total_ucr;

                return $datos;
            })->toArray();

            return [$key => [
                'total_ucr_1' => $item->sum('total_unique') > 0 ? round($item->sum('total_billables') / $item->sum('total_unique') * 100, 2) : 0,
                CallsReportService::CHILDREN_KEY => $total,
            ]];
        });

        return $list->toArray();
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
     * Apply filters to query builder.
     */
    private function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['buyers'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['buyers'] as $buyer) {
                    $q->orWhere('buyers.id', '=', "$buyer");
                }
            });
        }

        if (!empty($filters['states'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['states'] as $state) {
                    $q->orWhere('leads.state', 'LIKE', "%$state%");
                }
            });
        }

        if (!empty($filters['insurances'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['insurances'] as $insurance) {
                    $q->orWhereRaw('LOWER(JSON_EXTRACT(recordings.multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            });
        }

        if (!is_null($filters['hasCallIssues'])) {
            $query->where('recordings.multiple->call_ending_sooner_result', $filters['hasCallIssues']);

            if ($filters['hasCallIssues'] && count($filters['issuesTypes']) > 0) {
                $query->where(function ($q) use ($filters) {
                    foreach ($filters['issuesTypes'] as $issue) {
                        $q->orwhereRaw("JSON_CONTAINS(recordings.multiple, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                    }
                });
            }
        }

        return $query;
    }
}
