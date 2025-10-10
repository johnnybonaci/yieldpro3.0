<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\LeadApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class LeadsExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $lead_api_repository = app(LeadApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $leads = $lead_api_repository->leads($date_start, $date_end);

        return $leads->sortsFields('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'phone',
            'first_name',
            'last_name',
            'email',
            'type',
            'zip_code',
            'state',
            'data',
            'yp_lead_id',
            'campaign_name_id',
            'universal_lead_id',
            'trusted_form',
            'sub_id',
            'pub_list_id',
            'created_at',
            'cpl',
            'vendors_yp',
            'offers',
            'calls',
            'status',
        ];
    }
}
