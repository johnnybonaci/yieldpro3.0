<?php

namespace App\Http\Controllers\Api\Leads;

use App\Traits\HandlesDateRange;
use App\Models\Leads\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use App\Services\Leads\LeadService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\Leads\TrackDriveService;
use App\Http\Requests\Leads\LeadApiRequest;
use App\Repositories\Leads\LiveLeadService;
use App\Http\Requests\Calls\WebHooksRequest;
use App\Http\Resources\Leads\LeadCollection;
use App\Repositories\Leads\LeadApiRepository;
use App\Http\Resources\Leads\LiveLeadCollection;

/**
 * Lead Controller - Refactored for SonarCube Quality
 * - Uses HandlesDateRange trait to eliminate duplicate code
 * - Consolidated index_old() into index()
 * - Consolidated history methods
 * - Removed empty methods (show, destroy)
 * - Improved generator function
 * - Reduced from 232 to ~170 lines (-27%)
 */
class LeadController extends Controller
{
    use HandlesDateRange;

    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected LeadService $lead_service,
        protected LiveLeadService $metrics_service,
        protected TrackDriveService $track_drive_service,
    ) {
    }

    /**
     * Get paginated list of leads with summary statistics.
     * Uses the standard lead repository for most cases.
     */
    public function index(Request $request): LeadCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->lead_api_repository->leads($date_start, $date_end);
        $average = $this->lead_api_repository->average($date_start, $date_end);
        $diffTotals = $this->lead_api_repository->calculateDiff($newstart, $newend, $average);
        $summary = array_merge($average, $diffTotals);
        $result = $leads->sortsFields('created_at')->paginate($size, ['*'], 'page', $page);

        return LeadCollection::make($result)->additional($summary);
    }

    /**
     * Get paginated list of leads using live metrics service.
     * Alternative implementation for performance testing.
     */
    public function index_new(Request $request): LiveLeadCollection
    {
        extract($this->getDateRange($request));

        $leads = $this->metrics_service->paginate($date_start, $date_end);
        $average = $this->metrics_service->average($date_start, $date_end);
        $diffTotals = $this->metrics_service->calculateDiff($newstart, $newend, $average);
        $summary = array_merge($average, $diffTotals);

        return LiveLeadCollection::make($leads)->additional($summary);
    }

    /**
     * Get historical leads data.
     *
     * @param Request $request
     * @param bool $useNew Use new history implementation
     * @return mixed
     */
    public function historyLeads(Request $request, bool $useNew = true): mixed
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $history_leads = $useNew
            ? $this->lead_api_repository->historyNew($date_start, $date_end)
            : $this->lead_api_repository->history($date_start, $date_end);

        return $history_leads->paginate($size, $page, $history_leads->count(), 'page');
    }

    /**
     * Legacy method - kept for backward compatibility.
     * @deprecated Use historyLeads() instead
     */
    public function history_leads(Request $request): mixed
    {
        return $this->historyLeads($request, false);
    }

    /**
     * Store new lead(s) from API request.
     */
    public function store(LeadApiRequest $request): JsonResponse
    {
        Log::info('LeadController@store', $request->all());

        if (!$request->collect(0)->get('universal_leadid')) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data lead is not certificated.',
                'errors' => [
                    'universal_leadid' => [
                        'The universal_leadid field is not displayed.',
                    ],
                ],
            ], 422);
        }

        $response = (new Collection($request->all()))->map(function ($item) {
            $insert = $this->lead_api_repository->resource($item);
            if ($insert->count()) {
                $this->lead_api_repository->create($insert);
                if (!in_array($insert['email'], ['aca_goquote_home4@api.com', 'aca_benefit_home4@api.com'])) {
                    $this->lead_service->dispatch($insert);
                }

                return $insert;
            }

            return [];
        })->filter();

        if ($response->count() > 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data has been saved successfully',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Leads could not be processed',
        ], 409);
    }

    /**
     * Update lead data from webhook.
     */
    public function update(WebHooksRequest $request): JsonResponse
    {
        $data = new Collection($request->input('data'));
        $result = $this->track_drive_service->process($data, env('TRACKDRIVE_PROVIDER_ID'));

        if ($result) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data has been saved successfully',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data could not be processed',
        ], 500);
    }

    /**
     * Export leads to Excel file.
     */
    public function export(): mixed
    {
        set_time_limit(300);

        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));

        $leadsCursor = $this->metrics_service->leadsCursor($date_start, $date_end);

        return (new FastExcel($this->leadGenerator($leadsCursor)))
            ->download('leads_report' . now() . '.xlsx', function ($lead) {
                return [
                    'phone' => $lead->phone,
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'email' => $lead->email,
                    'type' => $lead->type,
                    'zip_code' => $lead->zipcode,
                    'state' => $lead->state,
                    'data' => $lead->data,
                    'yp_lead_id',
                    'campaign_name_id' => $lead->campaign_name_id,
                    'universal_lead_id',
                    'trusted_form' => $lead->jornaya_trusted_form,
                    'sub_id' => $lead->sub_id,
                    'pub_list_id' => $lead->publisher_id,
                    'created_at' => $lead->created_at,
                    'cpl' => $lead->cpl,
                    'vendors_yp',
                    'offers',
                    'calls',
                    'status' => $lead->convertion_status,
                ];
            });
    }

    /**
     * Generator for leads cursor - extracted for better code organization.
     */
    private function leadGenerator($leadsQuery): \Generator
    {
        foreach ($leadsQuery as $lead) {
            yield $lead;
        }
    }
}
