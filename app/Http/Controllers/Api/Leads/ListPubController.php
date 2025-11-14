<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\PubList;

/**
 * List Pubs Controller
 *
 * Refactored to use BaseListController (reduced from 24 to 13 lines).
 */
class ListPubController extends BaseListController
{
    protected function getModelClass(): string
    {
        return PubList::class;
    }
}
