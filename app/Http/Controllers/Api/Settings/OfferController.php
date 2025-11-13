<?php

namespace App\Http\Controllers\Api\Settings;

use App\Repositories\Leads\OfferRepository;

/**
 * Offer Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class OfferController extends BaseSettingsController
{
    public function __construct(OfferRepository $repository)
    {
        parent::__construct($repository);
    }
}
