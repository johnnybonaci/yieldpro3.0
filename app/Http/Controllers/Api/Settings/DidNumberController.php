<?php

namespace App\Http\Controllers\Api\Settings;

use App\Repositories\Leads\DidNumberRepository;

/**
 * DID Number Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class DidNumberController extends BaseSettingsController
{
    public function __construct(DidNumberRepository $repository)
    {
        parent::__construct($repository);
    }
}
