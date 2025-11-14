<?php

namespace App\Services\Leads;

use App\Models\Leads\Convertion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Lead Metrics Service
 *
 * Handles all metrics calculations, averages, CPL computations, and diff calculations.
 * Extracted from LeadApiRepository to comply with SonarCube standards.
 *
 * Responsibilities:
 * - Calculate averages and metrics
 * - Compute CPL (Cost Per Lead) data
 * - Calculate conversion metrics
 * - Generate diff calculations for comparisons
 * - Handle fast average computations
 */
class LeadMetricsService
{
    public const MAX_CPL = 'MAX(leads.cpl) as cpl';

    /**
     * Return average widget metrics.
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

    /**
     * Get CPL data for leads outside date range.
     */
    public function getCplOut(string $date_start, string $date_end, array $pubs_lists, bool $not = true): ?EloquentCollection
    {
        return Convertion::join('leads', function ($join) use ($date_start, $date_end) {
            $join->on('leads.phone', '=', 'convertions.phone_id')
                ->whereBetween('convertions.date_history', [$date_start, $date_end]);
        })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->selectRaw(self::MAX_CPL)
            ->whereNotBetween('leads.date_history', [$date_start, $date_end])
            ->when($not, fn ($query) => $query->whereNotIn('leads.pub_id', $pubs_lists))
            ->filterFields()
            ->groupBy('leads.phone')
            ->get();
    }

    /**
     * Get CPL data for leads inside date range.
     */
    public function getCplIn(string $date_start, string $date_end, array $pubs_lists, bool $not = true): ?EloquentCollection
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
            ->selectRaw(self::MAX_CPL)
            ->whereBetween('leads.date_history', [$date_start, $date_end])
            ->when($not, fn ($query) => $query->whereNotIn('leads.pub_id', $pubs_lists))
            ->filterFields()
            ->groupBy('leads.phone')
            ->get();
    }

    /**
     * Get CPL data for MassNexus traffic.
     */
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
            ->selectRaw(self::MAX_CPL)
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

    /**
     * Get total conversions with specific filters.
     */
    public function getTotalConvertions(string $date_start, string $date_end, string $columns, array $pubs_lists, bool $sale, bool $date, bool $not = true): ?Convertion
    {
        return Convertion::selectRaw($columns)
            ->join('leads', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->leftJoin('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->when($sale, fn ($query) => $query->whereBetween('leads.date_history', [$date_start, $date_end]))
            ->when($date, fn ($query) => $query->whereNotBetween('leads.date_history', [$date_start, $date_end]))
            ->when($not, fn ($query) => $query->whereNotIn('leads.pub_id', $pubs_lists))
            ->filterFields()
            ->first();
    }

    /**
     * Get total conversions grouped by campaign.
     */
    public function getTotalConvertionsCampaign(string $date_start, string $date_end, bool $sale, bool $date): array
    {
        $view_by = request()->input('view_by', 'leads.campaign_name_id') ?? 'leads.campaign_name_id';

        $columns_group = 'campaign_name_id, subs.sub_id, pubs.pub_list_id,pub_lists.name as vendors_yp,traffic_sources.name as vendors_td,sub_id2,sub_id3,sub_id4,leads.pub_id,traffic_sources.id as traffic_source_id';
        $columns_empty = ' "" as campaign_name_id, "" as sub_id, "" as pub_list_id, "" as  vendors_yp, "" as vendors_td';
        $columns = $view_by . ' as view_by, sum(revenue) AS revenue,sum(convertions.cpl) AS cpl,sum(calls) AS calls, sum(converted) AS converted, 0 as leads, sum(answered) as answered, leads.type';
        $group_by = $view_by == 'leads.campaign_name_id' ? ['leads.campaign_name_id', 'leads.sub_id3', 'leads.pub_id', 'convertions.traffic_source_id'] : $view_by;

        return \App\Models\Leads\Lead::selectRaw($columns)
            ->when($view_by == 'leads.type', fn ($query) => $query->selectRaw($columns_empty))
            ->when($view_by != 'leads.type', fn ($query) => $query->selectRaw($columns_group))
            ->join('convertions', function ($join) use ($date_start, $date_end) {
                $join->on('leads.phone', '=', 'convertions.phone_id')
                    ->whereBetween('convertions.date_history', [$date_start, $date_end]);
            })
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('offers', 'offers.id', '=', 'convertions.offer_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('traffic_sources', 'traffic_sources.id', '=', 'convertions.traffic_source_id')
            ->when($sale, fn ($query) => $query->whereBetween('leads.date_history', [$date_start, $date_end]))
            ->when($date, fn ($query) => $query->whereNotBetween('leads.date_history', [$date_start, $date_end]))
            ->filterFields()
            ->groupBy($group_by)
            ->get()
            ->toArray();
    }

    /**
     * Calculate sum average including previous period.
     */
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
     * Fast average calculation for campaigns.
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

        $totals_convertions = (object) [
            'revenue' => $totals_convertions_c->revenue + $totals_convertions_s->revenue,
            'cpl' => $totals_convertions_c->cpl + $totals_convertions_s->cpl,
            'calls' => $totals_convertions_c->calls + $totals_convertions_s->calls,
            'converted' => $totals_convertions_c->converted + $totals_convertions_s->converted,
            'answered' => $totals_convertions_c->answered + $totals_convertions_s->answered,
            'unique_calls' => $totals_convertions_c->unique_calls + $totals_convertions_s->unique_calls
        ];
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateAverage($totals_convertions, $total_leads);
    }

    /**
     * Fast average calculation for MassNexus.
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

        $totals_convertions = (object) [
            'revenue' => $totals_convertions_c->revenue + $totals_convertions_s->revenue,
            'cpl' => $totals_convertions_c->cpl + $totals_convertions_s->cpl,
            'calls' => $totals_convertions_c->calls + $totals_convertions_s->calls,
            'converted' => $totals_convertions_c->converted + $totals_convertions_s->converted,
            'answered' => $totals_convertions_c->answered + $totals_convertions_s->answered,
            'unique_calls' => $totals_convertions_c->unique_calls + $totals_convertions_s->unique_calls
        ];
        $total_leads = (object) ['leads' => $out_count, 'cpl' => $out_cpl];

        return $this->calculateAverage($totals_convertions, $total_leads);
    }

    /**
     * Calculate average metrics from conversion and lead data.
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
        $var = [
            'spend' => $spend,
            'revenue' => $revenue,
            'calls' => $calls,
            'converted' => $converted,
            'profit' => $profit,
            'leads' => $leads,
            'answered' => $answered,
            'unique_calls' => $unique_calls
        ];

        return $this->setAverage($var, 'average', $total_leads->cpl, $totals_convertions->cpl);
    }

    /**
     * Calculate sum average combining with previous averages.
     */
    public function calculateSumAverage(object $totals_convertions, object $total_leads, array $average): array
    {
        $spend = request()->input('date_record', 'date_created') == 'date_created'
            ? $average['sum']['total_spend']
            : $totals_convertions->cpl;
        $revenue = ($totals_convertions->revenue + $average['sum']['total_revenue']) ?? 0;
        $calls = ($totals_convertions->calls + $average['average']['total_calls']) ?? 0;
        $converted = ($totals_convertions->converted + $average['average']['total_billable']) ?? 0;
        $answered = ($totals_convertions->answered + $average['average']['total_answered']) ?? 0;
        $profit = $revenue - $spend;
        $leads = $total_leads->leads + $average['average']['total_leads'];
        $var = [
            'spend' => $spend,
            'revenue' => $revenue,
            'calls' => $calls,
            'converted' => $converted,
            'profit' => $profit,
            'leads' => $leads,
            'answered' => $answered
        ];

        return $this->setAverage($var, 'totals_avg', $total_leads->cpl, $totals_convertions->cpl);
    }

    /**
     * Set average metrics with calculated values.
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
     * Calculate diff between current and previous period.
     */
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

    /**
     * Calculate diff for MassNexus metrics.
     */
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
}
