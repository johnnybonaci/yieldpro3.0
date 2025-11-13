<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\Leads\BuyerRepository;

/**
 * Buyer Settings Controller
 * Extends BaseSettingsController to eliminate code duplication.
 */
class BuyerController extends BaseSettingsController
{
    public function __construct(BuyerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Update buyer transcription selection (custom endpoint).
     * This method is specific to Buyer and doesn't exist in other settings.
     */
    public function selection(Request $request): JsonResponse
    {
        /** @var BuyerRepository $repository */
        $repository = $this->repository;
        $result = $repository->saveSelection($request);

        return response()->json($result);
    }
}
