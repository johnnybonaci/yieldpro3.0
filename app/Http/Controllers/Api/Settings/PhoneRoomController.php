<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Leads\PhoneRoom;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\Paginator;
use App\Repositories\Leads\PhoneRoomRepository;

class PhoneRoomController extends Controller
{
    public function __construct(
        protected PhoneRoomRepository $phone_room_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->phone_room_repository->getPhoneRoom();

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PhoneRoom $phone_room)
    {
        return json_encode($this->phone_room_repository->savePhoneRoom($request, $phone_room));
    }
}
