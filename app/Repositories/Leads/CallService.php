<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CallService
{
    public function query(string $date_start, string $date_end): Builder
    {
        $request = request();

        $buyers = request()->input('select_buyers', []);
        $buyers = is_array($buyers) ? $buyers : explode(',', $buyers);
        $buyers = array_filter($buyers);

        $states = request()->input('select_states', []);
        $states = is_array($states) ? $states : explode(',', $states);
        $states = array_filter($states);

        $insurances = request()->input('select_insurances', []);
        $insurances = is_array($insurances) ? $insurances : explode(',', $insurances);
        $insurances = array_filter($insurances);

        $offers = request()->input('convertions_offer1id', []);
        $offers = is_array($offers) ? $offers : explode(',', $offers);
        $offers = array_filter($offers);

        $publisher = request()->input('pubs_pub1list1id', []);
        $publisher = is_array($publisher) ? $publisher : explode(',', $publisher);
        $publisher = array_filter($publisher);

        $subIds5 = request()->input('leads_sub1id5', []);
        $subIds5 = is_array($subIds5) ? $subIds5 : explode(',', $subIds5);
        $subIds5 = array_filter($subIds5);

        $hasCallIssues = request()->input('call_issues');
        $hasCallIssues = is_null($hasCallIssues) ? null : request()->boolean('call_issues');
        $issuesTypes = request()->collect('select_issues_types');

        return Call::query()
            ->when($request->filled('filter'), function ($query) use ($request) {
                $filters = $request->get('filter', []);

                foreach ($filters as $filter) {
                    $value = $filter['type'] === 'like' ? '%' . $filter['value'] . '%' : $filter['value'];

                    $query->where($filter['field'], $filter['type'], $value);
                }
            })
            ->when($request->filled('phone'), function ($query) use ($request) {
                return $query->where('phone_id', $request->input('phone'));
            })
            ->where(function ($query) use ($buyers) {
                foreach ($buyers as $buyer) {
                    $query->orWhere('buyer_name', 'LIKE', "%$buyer%");
                }
            })
            ->where(function ($query) use ($states) {
                foreach ($states as $state) {
                    $query->orWhere('state', 'LIKE', "%$state%");
                }
            })
            ->where(function ($query) use ($insurances) {
                foreach ($insurances as $insurance) {
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(multiple, "$.existing_insurance_name")) LIKE ?', ['%' . strtolower($insurance) . '%']);
                }
            })
            ->where(function ($query) use ($offers) {
                foreach ($offers as $offer) {
                    $query->orWhere('offer_id', $offer);
                }
            })
            ->where(function ($query) use ($publisher) {
                foreach ($publisher as $pub) {
                    $query->orWhere('lead_publisher_id', $pub);
                }
            })
            ->when($request->filled('convertions_traffic1source1id'), function ($query) use ($request) {
                $query->where('traffic_source_id', $request->input('convertions_traffic1source1id'));
            })
            ->where(function ($query) use ($subIds5) {
                foreach ($subIds5 as $subId5) {
                    $query->orWhere('lead_sub_id5', $subId5);
                }
            })
            ->when($request->filled('convertions_status'), function ($query) use ($request) {
                $query->where('status', $request->input('convertions_status'));
            })
            ->when($request->filled('recordings_status'), function ($query) use ($request) {
                $query->where('ai_status', $request->input('recordings_status'));
            })
            ->when($request->filled('recordings_billable'), function ($query) use ($request) {
                $query->where('ai_sale_status', $request->input('recordings_billable'));
            })
            ->when($request->filled('recordings_insurance'), function ($query) use ($request) {
                $query->where('ai_insurance_status', $request->input('recordings_insurance'));
            })
            ->when(!is_null($hasCallIssues), function ($query) use ($hasCallIssues, $issuesTypes) {
                $query->where('ai_analysis->call_ending_sooner_result', $hasCallIssues);

                if ($hasCallIssues && count($issuesTypes) > 0) {
                    $query->where(function ($query) use ($issuesTypes) {
                        foreach ($issuesTypes as $issue) {
                            $query->orwhereRaw("JSON_CONTAINS(ai_analysis, JSON_OBJECT('category', ?), '$.call_ending_sooner_reasons')", [$issue]);
                        }
                    });
                }
            })
            ->whereBetween('td_created_at_date', [$date_start, $date_end])
            ->when($request->filled('sort'), function ($query) use ($request) {
                foreach ($request->input('sort') as $sorter) {
                    $field = match ($sorter['field']) {
                        'phone_id' => 'phone_id',
                        'pub_list_id' => 'lead_publisher_id',
                        'sub_id5' => 'lead_sub_id5',
                        'offers' => 'offer_name',
                        'vendors_td' => 'traffic_source_name',
                        'state' => 'state',
                        'buyers' => 'buyer_name',
                        'status' => 'status',
                        'revenue' => 'revenue',
                        'durations' => 'durations',
                        'insurance' => 'ai_insurance_status',
                        'cpl' => 'cpl',
                        'terminating_phone' => 'terminating_phone',
                        'did_number_id' => 'did_number_id',
                        'date_sale' => 'created_at',
                        default => null,
                    };

                    if ($field) {
                        $query->orderBy($field, $sorter['dir']);
                    }
                }
            })->orderByDesc('td_created_at');
    }

    public function callsCursor(mixed $date_start, mixed $date_end): LazyCollection
    {
        return $this->query($date_start, $date_end)->cursor();
    }

    public function paginate(string $date_start, string $date_end): LengthAwarePaginator
    {
        $request = request();

        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        return $this->query($date_start, $date_end)->paginate($size, ['*'], 'page', $page);
    }

    public function average(string $date_start, string $date_end): array
    {
        $totals_convertions = $this->query($date_start, $date_end)
            ->selectRaw('
                sum(revenue) as revenue,
                sum(cpl) as cpl,
                sum(calls) as calls,
                sum(converted) as converted,
                sum(answered) as answered
            ')
            ->first();

        $subQuery = $this->query($date_start, $date_end)
            ->selectRaw('MAX(lead_cpl) as cpl')
            ->whereBetween('lead_created_at_date', [$date_start, $date_end])
            ->groupBy('phone_id');

        $leads_sale_in = DB::query()->fromSub($subQuery, 'calls')
            ->select(DB::raw('SUM(cpl) as cpl'), DB::raw('count(*) as total'))
            ->first();

        $total_leads = (object) ['leads' => $leads_sale_in->total, 'cpl' => $leads_sale_in->cpl];

        return (new LiveLeadService())->calculateAverage($totals_convertions, $total_leads);
    }

    public function calculateDiff(string $start, string $end, array $totals): array
    {
        $average = $this->average($start, $end);

        $lead_api_repository = new LiveLeadService();

        return $lead_api_repository->calculateDiffCalls($totals, $average);
    }
}
