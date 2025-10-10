<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\LeadApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class PageViewsExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $lead_api_repository = app(LeadApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $leads = $lead_api_repository->pageviews($date_start, $date_end);

        return $leads->sortsFields('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'ip',
            'utm_campaign',
            'url',
            'date',
            'created_at',
            'update_at',
        ];
    }
}
