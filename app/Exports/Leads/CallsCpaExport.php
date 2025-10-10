<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\CallsApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class CallsCpaExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $calls_api_repository = app(CallsApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $report = $calls_api_repository->reportCpa($date_start, $date_end);
        $viewBy = request()->input('view_by', 'convertions.buyer_id');
        $groupBy = [$viewBy, 'leads.state'];

        return $report->groupBy($groupBy)->sortsFields('total_sales')->get();
    }

    public function headings(): array
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id');
        $name = $viewBy == 'convertions.buyer_id' ? 'Buyer Name' : 'Traffic Source Name';

        return [
            'Revenue',
            'Total Cost',
            'Billables',
            'Unique Calls',
            '% UCR',
            'Sales',
            $name,
            'Cpa',
            'State',
        ];
    }

    public function map($row): array
    {
        return [
            $row->total_revenue,
            $row->total_cost,
            $row->total_billables,
            $row->total_unique,
            $row->total_ucr,
            $row->total_sales,
            $row->buyer_name,
            $row->total_cpa,
            $row->state,
        ];
    }

    public function title(): string
    {
        return 'CPA Details';
    }
}
