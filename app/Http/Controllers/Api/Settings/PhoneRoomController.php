<?php

namespace App\Http\Controllers\Api\Settings;

use App\Repositories\Leads\PhoneRoomRepository;

/**
 * Phone Room Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class PhoneRoomController extends BaseSettingsController
{
    public function __construct(PhoneRoomRepository $repository)
    {
        parent::__construct($repository);
    }
}
