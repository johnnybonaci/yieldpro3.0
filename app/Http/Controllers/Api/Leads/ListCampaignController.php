<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\LeadMetric;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCampaignController extends Controller
{
    public function __invoke(SearchRequest $request): LengthAwarePaginator
    {
        $campaigns = LeadMetric::query();

        $campaigns->search($request->search());

        return $campaigns->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );
    }
}
