<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\CallsApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class CallsRpcExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $calls_api_repository = app(CallsApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $report = $calls_api_repository->reportRpc($date_start, $date_end);
        $viewBy = request()->input('view_by', 'convertions.buyer_id');
        $groupBy = [$viewBy, 'leads.state'];

        return $report->groupBy($groupBy)->sortsFields('total_rpc')->get();
    }

    public function headings(): array
    {
        $viewBy = request()->input('view_by', 'convertions.buyer_id');
        $name = $viewBy == 'convertions.buyer_id' ? 'Buyer Name' : 'Traffic Source Name';

        return [
            $name,
            'Rpc',
            'Unique Calls',
            'Revenue',
            'Billables',
            'Durations',
            'State',
        ];
    }

    public function map($row): array
    {
        return [
            $row->buyer_name,
            $row->total_rpc,
            $row->total_unique,
            $row->total_revenue,
            $row->total_billables,
            $row->durations,
            $row->state,
        ];
    }

    public function title(): string
    {
        return 'RPC  Details';
    }
}
