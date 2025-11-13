<?php

namespace App\Http\Controllers\Api\Settings;

use App\Repositories\Leads\TrafficSourceRepository;

/**
 * Traffic Source Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class TrafficSourceController extends BaseSettingsController
{
    public function __construct(TrafficSourceRepository $repository)
    {
        parent::__construct($repository);
    }
}
