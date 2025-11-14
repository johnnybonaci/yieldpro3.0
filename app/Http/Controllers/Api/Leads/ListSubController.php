<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\Sub;

/**
 * List Subs Controller
 *
 * Refactored to use BaseListController (reduced from 24 to 13 lines).
 */
class ListSubController extends BaseListController
{
    protected function getModelClass(): string
    {
        return Sub::class;
    }
}
