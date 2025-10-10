<?php

namespace App\Providers;

use App\Filters\FiltersQueryBuilder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class FilterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::mixin(new FiltersQueryBuilder());
    }
}
