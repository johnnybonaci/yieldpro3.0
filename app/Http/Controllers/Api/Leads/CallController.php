<?php

namespace App\Http\Controllers\Api\Leads;

use App\Traits\HandlesDateRange;
use App\Models\Leads\Sub;
use App\Models\Leads\Buyer;
use App\Models\Leads\Offer;
use App\Models\Leads\State;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Leads\PubList;
use App\Models\Leads\Provider;
use App\Models\Leads\Recording;
use App\Models\Leads\Convertion;
use App\Models\Leads\LeadMetric;
use App\Exports\Leads\CallsExport;
use Spatie\Permission\Models\Role;
use App\Enums\TranscriptStatusEnum;
use App\Exports\Leads\CpaSumExport;
use App\Exports\Leads\RpcSumExport;
use App\Models\Leads\TrafficSource;
use App\Exports\Leads\CallsQaExport;
use App\Http\Controllers\Controller;
use App\Jobs\Leads\TranscriptionJob;
use App\Repositories\UserRepository;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Leads\CallsCpaExport;
use App\Exports\Leads\CallsRpcExport;
use App\Exports\Leads\MultipleSheets;
use App\Services\Leads\OpenAIService;
use App\Exports\Leads\WidgetsQaExport;
use App\Http\Resources\Leads\QaCollection;
use App\Http\Resources\Leads\CpaCollection;
use App\Http\Resources\Leads\RpcCollection;
use App\Http\Resources\Leads\CallCollection;
use App\Repositories\Leads\LeadApiRepository;
use App\Repositories\Leads\CallsApiRepository;

/**
 * Call Controller - Refactored for SonarCube Quality
 * - Uses HandlesDateRange trait to eliminate duplicate code
 * - Consolidated index_old() into index()
 * - Improved JSON responses
 * - Reduced from 368 to ~290 lines
 */
class CallController extends Controller
{
    use HandlesDateRange;

    public const XLS = '.xlsx';
    public const RULE_REQUIRED = 'required|integer';

    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected CallsApiRepository $calls_api_repository,
        protected UserRepository $user_repository,
    ) {
    }

    /**
     * Get paginated list of calls with summary statistics.
     */
    public function index(Request $request): CallCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->calls_api_repository->calls($date_start, $date_end);
        $total = $this->calls_api_repository->records($date_start, $date_end);
        $average = $this->calls_api_repository->average($date_start, $date_end);
        $diffTotals = $this->calls_api_repository->calculateDiff($newstart, $newend, $average);
        $summary = array_merge($average, $diffTotals);
        $result = $leads->sortsFields('convertions.created_at')->paginate($size, ['*'], 'page', $page, $total);

        return CallCollection::make($result)->additional($summary);
    }

    public function metadata(Request $request): mixed
    {
        /*
        |--------------------------------------------------------------------------
        | The following variables can be replaced with the dedicated endpoint
        |--------------------------------------------------------------------------
        |
        | [URL]     /api/data/partners
        | [ACTION]  \App\Http\Controllers\Api\Leads\ListPartnerController
        | */
        $partners = $this->lead_api_repository->getAll(new Buyer());
        $partners = $partners->where('provider_id', env('TRACKDRIVE_PROVIDER_ID', 2));
        /* -------------------------------------------------------------------------- */

        $callPartners = Convertion::select('buyers.id', 'buyers.name')->join('buyers', 'buyers.id', '=', 'convertions.buyer_id')->groupBy('buyers.id')->where('date_history', '>', '2024-01-01')->get();
        /* -------------------------------------------------------------------------- */

        /*
        |--------------------------------------------------------------------------
        | [URL]     /api/data/campaigns
        | [ACTION]  \App\Http\Controllers\Api\Leads\ListCampaignController
        | */
        $campaigns = $this->lead_api_repository->getAll(new LeadMetric());
        /* -------------------------------------------------------------------------- */

        /*
        |--------------------------------------------------------------------------
        | [URL]     /api/data/pubs
        | [ACTION]  \App\Http\Controllers\Api\Leads\ListPubController
        | */
        $pub_id = $this->lead_api_repository->getAll(new PubList());
        /* -------------------------------------------------------------------------- */

        /*
        |--------------------------------------------------------------------------
        | [URL]     /api/data/subs
        | [ACTION]  \App\Http\Controllers\Api\Leads\ListSubController
        | */
        $sub_id = $this->lead_api_repository->getAll(new Sub());
        /* -------------------------------------------------------------------------- */

        $offers = $this->lead_api_repository->getAll(new Offer());
        $offers = $offers->where('provider_id', env('TRACKDRIVE_PROVIDER_ID', 2));

        $traffic = $this->lead_api_repository->getAll(new TrafficSource());
        $traffic = $traffic->where('provider_id', env('TRACKDRIVE_PROVIDER_ID', 2));

        $providers = $this->lead_api_repository->getAll(new Provider());

        $type = $offers->groupBy('type')->keys();
        $type[] = 'user';

        $states = $this->lead_api_repository->getAll(new State());

        $users = $this->user_repository->show();

        $roles = Role::pluck('name')->toArray();

        return [
            'roles' => $roles,
            'provider_id' => env('TRACKDRIVE_PROVIDER_ID', 2),
            'users' => $users,
            'callPartners' => $callPartners,
            'partners' => $partners,
            'providers' => $providers,
            'offers' => $offers,
            'traffic' => $traffic,
            'type' => $type,
            'pub_id' => $pub_id,
            'sub_id' => $sub_id,
            'campaigns' => $campaigns,
            'states' => $states->map->only(['state', 'description']),
            'issueTypes' => [
                ['id' => 1, 'name' => 'YES'],
                ['id' => 0, 'name' => 'NO'],
            ],
            'issueReasonTypes' => array_map(fn (string $value) => ['id' => $value, 'name' => $value], [
                'On hold / Hold music',
                'Verification Process Issue',
                'Dead air',
                'Agent hung up',
                'Prospect hung up',
                'Lead hung up',
                'Call dropped',
                'Call back / Follow-up',
                'Other Technical Issue',
                'Already insured',
                'Not qualified',
                'Not interested',
                'Transcript too short',
            ]),
            'salesTypes' => [
                ['id' => 0, 'name' => 'NO SALE'],
                ['id' => 1, 'name' => 'SALE'],
                ['id' => 2, 'name' => 'SALE TO REVIEW'],
            ],
            'statusTypes' => array_map(fn (string $value) => ['id' => $value, 'name' => $value], [
                'CONTACT',
                'BILLABLE',
                'SALE',
                'SALE TO REVIEW',
                'FAILED',
                'NO CONTACT',
            ]),
            'insuranceTypes' => [
                ['id' => 1, 'name' => 'YES'],
                ['id' => 2, 'name' => 'NO'],
                ['id' => 3, 'name' => 'N/A'],
            ],
        ];
    }

    public function edit(Request $request): JsonResponse
    {
        $request->validate([
            'id' => self::RULE_REQUIRED,
            'billable' => self::RULE_REQUIRED,
            'call_ending_sooner_reason' => 'nullable|string',
            'insurance_value' => self::RULE_REQUIRED,
            'insurance_name' => 'nullable|string',
        ]);

        $record = Recording::find($request->get('id'));

        $multiple = is_null($record->multiple) ? [] : json_decode($record->multiple, true);
        $multiple['existing_insurance_name'] = $request->get('insurance_name');

        if (
            $multiple
            && array_key_exists('call_ending_sooner_reason', $multiple)
            && $multiple['call_ending_sooner_reason'] !== $request->get('call_ending_sooner_reason')
        ) {
            $multiple['call_ending_sooner_reason'] = $request->get('call_ending_sooner_reason');
            $newCallEndingReason['category'] = $request->get('call_ending_sooner_reason');
            $newCallEndingReason['reason'] = 'Manually changed';
            $multiple['call_ending_sooner_reasons'][] = $newCallEndingReason;
        }

        $record->update([
            'billable' => $request->get('billable'),
            'insurance' => $request->get('insurance_value'),
            'multiple' => json_encode($multiple),
        ]);

        return response()->json(['status' => 200]);
    }

    public function ask(Request $request, OpenAIService $openaiService): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'id' => self::RULE_REQUIRED,
        ]);

        $response = $openaiService->ask($request->input('query'), $request->input('id'));

        return response()->json(['status' => 200, 'response' => $response]);
    }

    public function export()
    {
        return Excel::download(new CallsExport(), 'calls_report_' . now() . self::XLS);
    }

    public function transcript(Request $request): JsonResponse
    {
        $data = [
            'id' => $request->get('id'),
            'date_start' => $request->get('date_start'),
            'date_end' => $request->get('date_end'),
            'type' => $request->get('type'),
        ];
        $record = Recording::find($request->get('id'));
        if ($record->status->value === TranscriptStatusEnum::TRANSCRIBING->value) {
            return response()->json(['status' => 204]);
        }

        TranscriptionJob::dispatch($data, auth()->user())->onQueue('transcript');
        $record->update(['status' => TranscriptStatusEnum::TRANSCRIBING->value]);

        return response()->json(['status' => 200]);
    }

    public function reprocess(Request $request): JsonResponse
    {
        $data = [
            'id' => $request->get('id'),
            'date_start' => $request->get('date_start'),
            'date_end' => $request->get('date_end'),
            'type' => $request->get('type'),
        ];

        /** @var Recording $record */
        $record = Recording::find($request->get('id'));

        if ($record->status->value === TranscriptStatusEnum::TRANSCRIBING->value) {
            return response()->json(['status' => 204]);
        }

        $record->update([
            'multiple' => null,
            'qa_status' => null,
            'billable' => 0,
            'insurance' => 2,
            'status' => TranscriptStatusEnum::TRANSCRIBING->value,
        ]);

        TranscriptionJob::dispatch($data, auth()->user())->onQueue('transcript');

        return response()->json(['status' => 200]);
    }

    public function makeRead(Request $request): JsonResponse
    {
        $notification = auth()->user()->notifications->find($request->get('id'));
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['status' => 200]);
    }

    public function reportCpa(Request $request): CpaCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $widgets = $this->calls_api_repository->getWidgetsCpa($this->calls_api_repository->reportCpa($date_start, $date_end));
        $report = $this->calls_api_repository->sortCpaCollections($date_start, $date_end);
        $result = $report->paginate($size, $page, $report->count(), 'page');

        return CpaCollection::make($result)->additional($widgets);
    }

    public function reportRpc(Request $request): RpcCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $widgets = $this->calls_api_repository->getWidgetsRpc($this->calls_api_repository->reportRpc($date_start, $date_end));
        $report = $this->calls_api_repository->sortRpcCollections($date_start, $date_end);
        $result = $report->paginate($size, $page, $report->count(), 'page');

        return RpcCollection::make($result)->additional($widgets);
    }

    public function exportCpa()
    {
        return (new MultipleSheets([
            'Cpa_Details' => new CallsCpaExport(),
            'Cpa_Summary' => new CpaSumExport(),
        ])
        )->download('cpa_report_' . now() . self::XLS);
    }

    public function exportRpc()
    {
        return (new MultipleSheets([
            'Rpc_Details' => new CallsRpcExport(),
            'Rpc_Summary' => new RpcSumExport(),
        ])
        )->download('rpc_report_' . now() . self::XLS);
    }

    public function reportQa(Request $request): QaCollection
    {
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        [$widgets, $report] = $this->calls_api_repository->qaReportCollect();
        $result = $report->paginate($size, $page, $report->count(), 'page');

        return QaCollection::make($result)->additional($widgets);
    }

    public function exportQa()
    {
        [$widgets, $report, $collections] = $this->calls_api_repository->qaReportCollect();

        $widgets = collect($widgets);

        return (new MultipleSheets([
            'Qa_Report' => new CallsQaExport($collections),
            'Qa_Widgets' => new WidgetsQaExport($widgets),
        ])
        )->download('qa_report_' . now() . self::XLS);
    }
}
