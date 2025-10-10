<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\PubList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPubController extends Controller
{
    public function __invoke(SearchRequest $request): LengthAwarePaginator
    {
        $pubs = PubList::query();

        $pubs->search($request->search());

        return $pubs->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );
    }
}
