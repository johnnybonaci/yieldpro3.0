<?php

namespace App\Contracts;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interface for Settings Repository pattern.
 * Ensures consistent behavior across all settings repositories.
 */
interface SettingsRepositoryInterface
{
    /**
     * Get query builder for items with optional filtering and eager loading.
     */
    public function getQuery(): Builder;

    /**
     * Save or update an item from request data.
     *
     * @return array Response data
     */
    public function save(Request $request, Model $model): array;

    /**
     * Get the default sort field for the repository.
     */
    public function getDefaultSortField(): string;
}
