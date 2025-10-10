<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\Sub;
use App\Models\Leads\Call;
use App\Models\Leads\Buyer;
use App\Models\Leads\Offer;
use App\Models\Leads\State;
use Illuminate\Http\Request;
use App\Models\Leads\PubList;
use App\Models\Leads\Provider;
use App\Models\Leads\Recording;
use App\Models\Leads\Convertion;
use App\Models\Leads\LeadMetric;
use App\Jobs\Leads\UpdateCallJob;
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
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\Leads\CallsCpaExport;
use App\Exports\Leads\CallsRpcExport;
use App\Exports\Leads\MultipleSheets;
use App\Services\Leads\OpenAIService;
use App\Exports\Leads\WidgetsQaExport;
use App\Repositories\Leads\CallService;
use App\Http\Resources\Leads\QaCollection;
use App\Http\Resources\Leads\CpaCollection;
use App\Http\Resources\Leads\RpcCollection;
use App\Http\Resources\Leads\CallCollection;
use App\Repositories\Leads\LeadApiRepository;
use App\Repositories\Leads\CallsApiRepository;
use App\Http\Resources\Leads\CallNewCollection;

class CallController extends Controller
{
    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected CallsApiRepository $calls_api_repository,
        protected UserRepository $user_repository,
        protected CallService $callService,
    ) {
    }

    public function index(Request $request): mixed
    {
        $user = $request->user();

        if (in_array($user->id, config('app.performance.test_users'))) {
            return $this->index_old($request);
        }

        return $this->index_old($request);
    }

    public function index_old(Request $request): CallCollection
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        extract(__toRangePassDay($date_start, $date_end));

        $leads = $this->calls_api_repository->calls($date_start, $date_end);
        $total = $this->calls_api_repository->records($date_start, $date_end);
        $average = $this->calls_api_repository->average($date_start, $date_end);
        $diffTotals = $this->calls_api_repository->calculateDiff($newstart, $newend, $average);
        $summary = array_merge($average, $diffTotals);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $leads->sortsFields('convertions.created_at')->paginate($size, ['*'], 'page', $page, $total);

        return CallCollection::make($result)->additional($summary);
    }

    public function index_new(Request $request): mixed
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));

        extract(__toRangePassDay($date_start, $date_end));

        $calls = $this->callService->paginate($date_start, $date_end);
        $average = $this->callService->average($date_start, $date_end);
        $diffTotals = $this->callService->calculateDiff($newstart, $newend, $average);

        $summary = array_merge($average, $diffTotals);

        return CallNewCollection::make($calls)->additional($summary);
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

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'billable' => 'required|integer',
            'call_ending_sooner_reason' => 'nullable|string',
            'insurance_value' => 'required|integer',
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
        // UpdateCallJob::dispatchSync($record->id);

        return json_encode(['status' => 200]);
    }

    public function ask(Request $request, OpenAIService $openaiService)
    {
        $request->validate([
            'query' => 'required|string',
            'id' => 'required|integer',
        ]);

        $response = $openaiService->ask($request->input('query'), $request->input('id'));

        return json_encode(['status' => 200, 'response' => $response]);
    }

    public function export()
    {
        return Excel::download(new CallsExport(), 'calls_report_' . now() . '.xlsx');
    }

    public function export_new()
    {
        set_time_limit(300);

        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));

        $callsCursor = $this->callService->callsCursor($date_start, $date_end);

        function usersGenerator($callsCursor)
        {
            foreach ($callsCursor as $lead) {
                yield $lead;
            }
        }

        return (new FastExcel(usersGenerator($callsCursor)))->download('calls_report_' . now() . '.xlsx', function (Call $call) {
            return [
                'phone' => $call->phone_id,
                'state' => $call->state,
                'status calls' => $call->status,
                'insurance name' => __toAnalisys($call->getRawOriginal('ai_analysis'), 'existing_insurance_name'),
                'revenue' => $call->revenue,
                'cpl' => $call->cpl,
                'durations' => $call->durations,
                'calls' => $call->calls,
                'converted' => $call->converted,
                'terminating_phone' => $call->terminating_phone,
                'did number' => $call->did_number_id,
                'date_sale' => $call->created_at->format('Y-m-d H:i:s'),
                'Sales' => $call->ai_sale_status,
                'offers' => $call->offer_name,
                'buyers' => $call->buyer_name,
                'vendors_td' => $call->traffic_source_name,
                'pub id' => $call->lead_publisher_id,
                'Sale Conclusion' => __toAnalisys($call->getRawOriginal('ai_analysis'), 'sale_analysis'),
                'Sentiment Analysis' => __toAnalisys($call->getRawOriginal('ai_analysis'), 'sentiment_analysis'),
                'Call Ending Issues Status' => match ($call->ai_analysis['call_ending_sooner_result'] ?? null) {
                    true => 'YES',
                    false => 'NO',
                    default => 'N/A',
                },
                'Call Ending Analysis' => __toAnalisys($call->getRawOriginal('ai_analysis'), 'call_ending_analysis'),
                'Call Ending Reason' => $call->ai_analysis['call_ending_sooner_reason']
                    ?? $call->ai_analysis['call_ending_sooner_reasons'][0]['category']
                    ?? '',
            ];
        });
    }

    public function transcript(Request $request)
    {
        $data = [
            'id' => $request->get('id'),
            'date_start' => $request->get('date_start'),
            'date_end' => $request->get('date_end'),
            'type' => $request->get('type'),
        ];
        $record = Recording::find($request->get('id'));
        if ($record->status->value === TranscriptStatusEnum::TRANSCRIBING->value) {
            return json_encode(['status' => 204]);
        }

        TranscriptionJob::dispatch($data, auth()->user())->onQueue('transcript');
        $record->update(['status' => TranscriptStatusEnum::TRANSCRIBING->value]);
        // UpdateCallJob::dispatchSync($record->id);

        return json_encode(['status' => 200]);
    }

    public function reprocess(Request $request)
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
            return json_encode(['status' => 204]);
        }

        $record->update([
            'multiple' => null,
            'qa_status' => null,
            'billable' => 0,
            'insurance' => 2,
            'status' => TranscriptStatusEnum::TRANSCRIBING->value,
        ]);

        TranscriptionJob::dispatch($data, auth()->user())->onQueue('transcript');
        // UpdateCallJob::dispatchSync($record->id);

        return json_encode(['status' => 200]);
    }

    public function makeRead(Request $request)
    {
        $notification = auth()->user()->notifications->find($request->get('id'));
        if ($notification) {
            $notification->markAsRead();
        }

        return json_encode(['status' => 200]);
    }

    public function reportCpa(Request $request): CpaCollection
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        $widgets = $this->calls_api_repository->getWidgetsCpa($this->calls_api_repository->reportCpa($date_start, $date_end));
        $report = $this->calls_api_repository->sortCpaCollections($date_start, $date_end);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $report->paginate($size, 'page', $page, $report->count());

        return CpaCollection::make($result)->additional($widgets);
    }

    public function reportRpc(Request $request): RpcCollection
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        $widgets = $this->calls_api_repository->getWidgetsRpc($this->calls_api_repository->reportRpc($date_start, $date_end));
        $report = $this->calls_api_repository->sortRpcCollections($date_start, $date_end);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $report->paginate($size, 'page', $page, $report->count());

        return RpcCollection::make($result)->additional($widgets);
    }

    public function exportCpa()
    {
        return (new MultipleSheets([
            'Cpa_Details' => new CallsCpaExport(),
            'Cpa_Summary' => new CpaSumExport(),
        ])
        )->download('cpa_report_' . now() . '.xlsx');
    }

    public function exportRpc()
    {
        return (new MultipleSheets([
            'Rpc_Details' => new CallsRpcExport(),
            'Rpc_Summary' => new RpcSumExport(),
        ])
        )->download('rpc_report_' . now() . '.xlsx');
    }

    public function reportQa(Request $request): QaCollection
    {
        [$widgets, $report] = $this->calls_api_repository->qaReportCollect();
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $report->paginate($size, 'page', $page, $report->count());

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
        )->download('qa_report_' . now() . '.xlsx');
    }
}
