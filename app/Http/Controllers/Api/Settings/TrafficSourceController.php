<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Leads\TrafficSource;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\Paginator;
use App\Repositories\Leads\TrafficSourceRepository;

class TrafficSourceController extends Controller
{
    public function __construct(
        protected TrafficSourceRepository $trafficsource_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->trafficsource_repository->getTrafficSource();

        $rows->with('provider');

        return $rows->filterFields()->sortsFields('updated_at')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrafficSource $traffic_source)
    {
        return json_encode($this->trafficsource_repository->saveTrafficSource($request, $traffic_source));
    }
}
