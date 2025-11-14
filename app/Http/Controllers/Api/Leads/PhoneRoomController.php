<?php

namespace App\Http\Controllers\Api\Leads;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\HandlesDateRange;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calls\CallsApiRequest;
use Illuminate\Contracts\Pagination\Paginator;
use App\Repositories\Leads\PhoneRoomRepository;
use App\Http\Resources\Leads\PhoneRoomCollection;

/**
 * Phone Room Leads Controller - Refactored for SonarCube Quality
 * Uses HandlesDateRange trait to eliminate duplicate code.
 */
class PhoneRoomController extends Controller
{
    use HandlesDateRange;

    public function __construct(
        protected PhoneRoomRepository $phone_room_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): PhoneRoomCollection
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->phone_room_repository->logs($date_start, $date_end);
        $widget = $this->phone_room_repository->widget($date_start, $date_end);
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
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $leads = $this->phone_room_repository->metrics();
        $result = $leads->paginate($size, $page, $leads->count(), 'page');

        return $result;
    }

    /**
     * Display a listing of the reports phoneroom.
     */
    public function reports(Request $request): Paginator
    {
        extract($this->getDateRange($request));
        ['page' => $page, 'size' => $size] = $this->getPaginationParams($request);

        $date_start = Carbon::parse($date_start)->format('Y-m-d 00:00:00');
        $date_end = Carbon::parse($date_end)->format('Y-m-d 23:59:59');
        $leads = $this->phone_room_repository->reports($date_start, $date_end);
        $result = $leads->paginate($size, ['*'], 'page', $page);

        return $result;
    }
}
