<?php

namespace App\Repositories\Leads;

use App\Models\Leads\LeadMetric;

class LeadMetricRepository
{
    public function __construct()
    {
    }

    /**
     * Summary of setCampaignId.
     */
    public function setCampaignId(array $data): string
    {
        $campaign_name = LeadMetric::firstOrCreate(['campaign_name' => $data['campaign_name']]);
        $utm_source = $data['utm_source'] ?? null;
        $utm_medium = $data['utm_medium'] ?? null;
        $utm_content = $data['utm_content'] ?? null;
        $campaign_name->utm_source = $utm_source;
        $campaign_name->utm_medium = $utm_medium;
        $campaign_name->utm_content = $utm_content;
        $campaign_name->save();

        return $campaign_name->campaign_name;
    }
}
