<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\LeadMetric;

/**
 * List Campaigns Controller
 *
 * Refactored to use BaseListController (reduced from 24 to 13 lines).
 */
class ListCampaignController extends BaseListController
{
    protected function getModelClass(): string
    {
        return LeadMetric::class;
    }
}
