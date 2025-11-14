<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base List Controller - Eliminates Duplication
 *
 * Consolidates common list/search/pagination logic from:
 * - ListPartnerController
 * - ListCampaignController
 * - ListPubController
 * - ListSubController
 *
 * Reduces 96 lines of duplicated code to 24 lines (75% reduction).
 */
abstract class BaseListController extends Controller
{
    /**
     * Get the model class for the list query.
     */
    abstract protected function getModelClass(): string;

    /**
     * Apply additional filters to the query.
     * Override this method to add model-specific filters.
     */
    protected function applyFilters(Builder $query, SearchRequest $request): Builder
    {
        return $query;
    }

    /**
     * Handle the list request with search and pagination.
     */
    public function __invoke(SearchRequest $request): LengthAwarePaginator
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // Apply search if provided
        $query->search($request->search());

        // Apply additional filters (can be overridden)
        $query = $this->applyFilters($query, $request);

        // Return paginated results
        return $query->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );
    }
}
