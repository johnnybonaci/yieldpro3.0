<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\Sub;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSubController extends Controller
{
    public function __invoke(SearchRequest $request): LengthAwarePaginator
    {
        $subs = Sub::query();

        $subs->search($request->search());

        return $subs->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );
    }
}
