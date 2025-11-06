<?php

namespace App\Http\Controllers\Api\Leads;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\LeadApiRepository;
use App\Http\Resources\Leads\PageViewsCollection;

class LeadPageViewController extends Controller
{
    public function __construct(
        protected LeadApiRepository $lead_api_repository,
    ) {
    }

    /**
     * Display a listing of the reports phoneroom.
     */
    public function index(Request $request)
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));
        $leads = $this->lead_api_repository->pageviews($date_start, $date_end);
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $result = $leads->paginate($size, $page, $leads->count(), 'page');

        return PageViewsCollection::make($result);
    }
}
