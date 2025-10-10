<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\LeadApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class CampaignExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $lead_api_repository = app(LeadApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $leads = $lead_api_repository->campaignDashboard($date_start, $date_end);

        return $leads;
    }

    public function headings(): array
    {
        return [
            'campaign_name',
            'vendors_td',
            'vendors_yp',
            'type',
            'pub_id',
            'sub_id',
            'total_leads',
            'total_calls',
            'total_sales',
            'total_spend',
            'total_spend_leads',
            'total_spend_calls',
            'gross_revenue',
            'gross_profit',
            'gross_margin',
            'cost_per_lead',
            'cost_per_calls',
            'cost_per_sales',
            'revenue_per_sale',
            'revenue_per_call',
            'call_per',
            'cpa_per',
        ];
    }
}
