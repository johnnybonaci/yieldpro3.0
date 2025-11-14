<?php

namespace App\Http\Controllers\Api\Leads;

use App\Traits\HandlesDateRange;
use Illuminate\Http\Request;
use App\Services\Leads\LeadService;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Leads\CampaignExport;
use App\Exports\Leads\CampaignMnExport;
use App\Repositories\Leads\LeadApiRepository;
use App\Http\Resources\Leads\CampaignDashboardCollection;

/**
 * Campaign Controller - Refactored for SonarCube Quality
 * Uses HandlesDateRange trait to eliminate duplicate code
 */
class CampaignController extends Controller
{
    use HandlesDateRange;

    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected LeadService $lead_service,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CampaignDashboardCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->lead_api_repository->campaignDashboard($date_start, $date_end);
        $average = $this->lead_api_repository->fastAverage($date_start, $date_end);
        $diffTotals = $this->lead_api_repository->calculateDiff($newstart, $newend, $average, true);
        $summary = array_merge($average, $diffTotals);
        $result = $leads->paginate($size, $page, $leads->count(), 'page');

        return CampaignDashboardCollection::make($result)->additional($summary);
    }

    public function export()
    {
        return Excel::download(new CampaignExport(), 'campaign_dashboard_' . now() . '.xlsx');
    }

    /**
     * Display a listing of the resource for MassNexus.
     */
    public function campaign_mn(Request $request): CampaignDashboardCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->lead_api_repository->campaignMn($date_start, $date_end);
        $request->merge(['convertions_traffic1source1id' => 10002]);
        $average = $this->lead_api_repository->fastAverageMn($date_start, $date_end);
        $request->merge(['convertions_traffic1source1id' => 10002]);
        $diffTotals = $this->lead_api_repository->calculateDiffMn($newstart, $newend, $average, true);
        $summary = array_merge($average, $diffTotals);
        $result = $leads->paginate($size, $page, $leads->count(), 'page');

        return CampaignDashboardCollection::make($result)->additional($summary);
    }

    public function export_mn()
    {
        return Excel::download(new CampaignMnExport(), 'report_massnexus_' . now() . '.xlsx');
    }
}
