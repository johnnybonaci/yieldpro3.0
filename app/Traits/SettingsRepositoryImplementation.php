<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Settings Repository Implementation Trait
 *
 * Provides default implementation for SettingsRepositoryInterface.
 * Can be used in both abstract classes and classes that extend other base classes.
 */
trait SettingsRepositoryImplementation
{
    /**
     * Get the query builder for this repository's items.
     */
    abstract protected function getSettingsQuery(): Builder;

    /**
     * Save the model using repository-specific logic.
     */
    abstract protected function saveSettings(Request $request, Model $model): array;

    /**
     * Get the default sort field.
     * Override this method if 'id' is not the default sort field.
     */
    protected function getDefaultSort(): string
    {
        return 'id';
    }

    /**
     * Implementation of SettingsRepositoryInterface::getQuery().
     */
    final public function getQuery(): Builder
    {
        return $this->getSettingsQuery();
    }

    /**
     * Implementation of SettingsRepositoryInterface::save().
     */
    final public function save(Request $request, Model $model): array
    {
        return $this->saveSettings($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface::getDefaultSortField().
     */
    final public function getDefaultSortField(): string
    {
        return $this->getDefaultSort();
    }
}
