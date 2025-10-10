<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Leads\PubList;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\PubRepository;
use Illuminate\Contracts\Pagination\Paginator;

class PubIdController extends Controller
{
    public function __construct(
        protected PubRepository $pub_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->pub_repository->getPubList();

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Create the specified resource in storage.
     */
    public function create(Request $request)
    {
        return json_encode($this->pub_repository->savePubs($request));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PubList $pubid)
    {
        return json_encode($this->pub_repository->savePubs($request, $pubid));
    }

    /**
     * Get pubs by offer id.
     */
    public function pubsByOffer(int $offerid)
    {
        return $this->pub_repository->listPubsByOffer($offerid);
    }
}
