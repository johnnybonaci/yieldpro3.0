<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\Buyer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPartnerController extends Controller
{
    public function __invoke(SearchRequest $request): LengthAwarePaginator
    {
        $partners = Buyer::query();

        $partners->search($request->search());

        $partners->where('provider_id', env('TRACKDRIVE_PROVIDER_ID', 2));

        return $partners->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );
    }
}
