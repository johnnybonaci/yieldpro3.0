<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * Searchable Trait - Eliminates Duplication
 *
 * Consolidates search scope pattern from 4 models:
 * - Buyer
 * - Sub
 * - LeadMetric
 * - PubList
 *
 * Reduces ~40 lines of duplicated search code.
 *
 * Usage:
 * Define $searchableColumns property in your model:
 * protected $searchableColumns = ['table.column1', 'table.column2'];
 */
trait Searchable
{
    /**
     * Get the searchable columns for this model.
     * Override this method to customize searchable fields.
     *
     * @return array
     */
    protected function getSearchableColumns(): array
    {
        return $this->searchableColumns ?? ['name'];
    }

    /**
     * Scope a query to search across specified columns.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            $search = strtolower($search);
            $columns = $this->getSearchableColumns();

            return $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $q->where(DB::raw("LOWER({$column})"), 'like', "%{$search}%");
                    } else {
                        $q->orWhere(DB::raw("LOWER({$column})"), 'like', "%{$search}%");
                    }
                }
            });
        });
    }
}
