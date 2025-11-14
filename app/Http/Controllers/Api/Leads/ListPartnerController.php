<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\Buyer;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Database\Eloquent\Builder;

/**
 * List Partners Controller
 *
 * Refactored to use BaseListController (reduced from 25 to 16 lines).
 */
class ListPartnerController extends BaseListController
{
    protected function getModelClass(): string
    {
        return Buyer::class;
    }

    protected function applyFilters(Builder $query, SearchRequest $request): Builder
    {
        return $query->where('provider_id', env('TRACKDRIVE_PROVIDER_ID', 2));
    }
}
