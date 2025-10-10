<?php

namespace App\Http\Controllers\Api\Leads;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calls\CallsApiRequest;
use Illuminate\Contracts\Pagination\Paginator;
use App\Repositories\Leads\PhoneRoomRepository;
use App\Http\Resources\Leads\PhoneRoomCollection;

class PhoneRoomController extends Controller
{
    public function __construct(
        protected PhoneRoomRepository $phone_room_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): PhoneRoomCollection
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));

        $leads = $this->phone_room_repository->logs($date_start, $date_end);
        $widget = $this->phone_room_repository->widget($date_start, $date_end);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $leads->sortsFields('created_at')->paginate($size, ['*'], 'page', $page);

        return PhoneRoomCollection::make($result)->additional($widget);
    }

    /**
     * save history data from the phone room.
     */
    public function store(CallsApiRequest $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data has been saved successfully',
        ], 200);
    }

    /**
     * Display a listing of the metrics phoneroom.
     */
    public function metrics(Request $request): Paginator
    {
        $leads = $this->phone_room_repository->metrics();
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $leads->paginate($size, 'page', $page, $leads->count());

        return $result;
    }

    /**
     * Display a listing of the reports phoneroom.
     */
    public function reports(Request $request): Paginator
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        $date_start = Carbon::parse($date_start)->format('Y-m-d 00:00:00');
        $date_end = Carbon::parse($date_end)->format('Y-m-d 23:59:59');
        $leads = $this->phone_room_repository->reports($date_start, $date_end);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $leads->paginate($size, ['*'], 'page', $page);

        return $result;
    }
}
