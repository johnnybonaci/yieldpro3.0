<?php

namespace App\Http\Controllers\Api\Settings;

use App\Models\Leads\Offer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\OfferRepository;
use Illuminate\Contracts\Pagination\Paginator;

class OfferController extends Controller
{
    public function __construct(
        protected OfferRepository $offer_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->offer_repository->getOffers();

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Offer $offer)
    {
        return json_encode($this->offer_repository->saveOffers($request, $offer));
    }
}
