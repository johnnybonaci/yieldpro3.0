<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_leads' => $this['total_leads'],
            'total_calls' => $this['total_calls'],
            'total_answered' => $this['total_answered'],
            'total_sales' => $this['total_sales'],
            'total_spend' => $this['total_spend'],
            'total_spend_leads' => $this['total_spend_leads'],
            'total_spend_calls' => $this['total_spend_calls'],
            'cost_per_lead' => $this['cost_per_lead'],
            'rev_per_lead' => $this['rev_per_lead'],
            'cost_per_calls' => $this['cost_per_calls'],
            'rev_per_calls' => $this['rev_per_calls'],
            'cost_per_sales' => $this['cost_per_sales'],
            'gross_revenue' => $this['gross_revenue'],
            'gross_profit' => $this['gross_profit'],
            'gross_margin' => $this['gross_margin'],
            'revenue_per_sale' => $this['revenue_per_sale'],
            'revenue_per_call' => $this['revenue_per_call'],
            'call_per' => $this['call_per'],
            'cpa_per' => $this['cpa_per'],
            'type' => $this['type'],
            'pub_id' => $this['pub_id'],
            'sub_id' => $this['sub_id'],
            'sub_id2' => $this['sub_id2'],
            'sub_id3' => $this['sub_id3'],
            'sub_id4' => $this['sub_id4'],
            'vendors_yp' => $this['vendors_yp'],
            'vendors_td' => $this['vendors_td'],
            'campaign_name' => $this['campaign_name'],
        ];
    }
}
