<?php

namespace App\Http\Controllers\Api\Settings;

use App\Repositories\Leads\ProviderRepository;

/**
 * Provider Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class ProviderController extends BaseSettingsController
{
    public function __construct(ProviderRepository $repository)
    {
        parent::__construct($repository);
    }
}
