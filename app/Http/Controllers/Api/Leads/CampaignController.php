<?php

namespace App\Http\Controllers\Api\Leads;

use Illuminate\Http\Request;
use App\Services\Leads\LeadService;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Leads\CampaignExport;
use App\Exports\Leads\CampaignMnExport;
use App\Repositories\Leads\LeadApiRepository;
use App\Http\Resources\Leads\CampaignDashboardCollection;

class CampaignController extends Controller
{
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
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        extract(__toRangePassDay($date_start, $date_end));
        $leads = $this->lead_api_repository->campaignDashboard($date_start, $date_end);
        $average = $this->lead_api_repository->fastAverage($date_start, $date_end);
        $diffTotals = $this->lead_api_repository->calculateDiff($newstart, $newend, $average, true);
        $summary = array_merge($average, $diffTotals);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        $result = $leads->paginate($size, 'page', $page, $leads->count());

        return CampaignDashboardCollection::make($result)->additional($summary);
    }

    public function export()
    {
        return Excel::download(new CampaignExport(), 'campaign_dashboard_' . now() . '.xlsx');
    }

    /**
     * Display a listing of the resource.
     */
    public function campaign_mn(Request $request): CampaignDashboardCollection
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        extract(__toRangePassDay($date_start, $date_end));

        $leads = $this->lead_api_repository->campaignMn($date_start, $date_end);
        $request->merge(['convertions_traffic1source1id' => 10002]);
        $average = $this->lead_api_repository->fastAverageMn($date_start, $date_end);
        $request->merge(['convertions_traffic1source1id' => 10002]);
        $diffTotals = $this->lead_api_repository->calculateDiffMn($newstart, $newend, $average, true);
        $summary = array_merge($average, $diffTotals);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        $result = $leads->paginate($size, 'page', $page, $leads->count());

        return CampaignDashboardCollection::make($result)->additional($summary);
    }

    public function export_mn()
    {
        return Excel::download(new CampaignMnExport(), 'report_massnexus_' . now() . '.xlsx');
    }
}
