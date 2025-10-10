<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Leads\DidNumber;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\Paginator;
use App\Repositories\Leads\DidNumberRepository;

class DidNumberController extends Controller
{
    public function __construct(
        protected DidNumberRepository $did_number_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->did_number_repository->getDidNumbers();

        $rows->with('traffic_sources', 'offers');

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DidNumber $did_number)
    {
        return json_encode($this->did_number_repository->saveDidNumbers($request, $did_number));
    }
}
