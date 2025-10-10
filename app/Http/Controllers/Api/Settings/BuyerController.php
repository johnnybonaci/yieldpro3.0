<?php

namespace App\Http\Controllers\Api\Settings;

use App\Models\Leads\Buyer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\BuyerRepository;
use Illuminate\Contracts\Pagination\Paginator;

class BuyerController extends Controller
{
    public function __construct(
        protected BuyerRepository $buyer_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->buyer_repository->getBuyers();

        $rows->with('provider');

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Buyer $buyer)
    {
        return json_encode($this->buyer_repository->saveBuyers($request, $buyer));
    }

    /**
     * Update the specified resource in storage.
     */
    public function selection(Request $request)
    {
        return json_encode($this->buyer_repository->saveSelection($request));
    }
}
