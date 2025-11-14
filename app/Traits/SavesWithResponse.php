<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Saves With Response Trait - Eliminates Duplication
 *
 * Consolidates save-response pattern from 7 repositories:
 * - BuyerRepository
 * - ProviderRepository
 * - TrafficSourceRepository
 * - DidNumberRepository
 * - OfferRepository
 * - PhoneRoomRepository
 * - PubRepository
 *
 * Reduces ~175 lines of duplicated code.
 */
trait SavesWithResponse
{
    /**
     * Save model with standardized response format.
     *
     * @param Model $model The model to save
     * @param string $entityName Human-readable entity name (e.g., 'Buyer', 'Offer')
     * @param callable $fieldSetter Callback to set model fields from request
     * @return array Standard response array with icon, message, response
     */
    protected function saveWithResponse(
        Model $model,
        string $entityName,
        callable $fieldSetter
    ): array {
        $icon = 'success';
        $message = "The {$entityName} has been successfully updated";

        // Apply field setter callback
        $fieldSetter($model);

        // Set updated timestamp
        $model->updated_at = now();

        // Attempt save
        $saved = $model->save();

        // Update response if save failed
        if (!$saved) {
            $icon = 'error';
            $message = "The {$entityName} has not been updated";
        }

        return [
            'icon' => $icon,
            'message' => $message,
            'response' => $saved,
        ];
    }

    /**
     * Save model for create operation with standardized response.
     *
     * @param Model $model The model to save
     * @param string $entityName Human-readable entity name
     * @param callable $fieldSetter Callback to set model fields from request
     * @return array Standard response array
     */
    protected function createWithResponse(
        Model $model,
        string $entityName,
        callable $fieldSetter
    ): array {
        $icon = 'success';
        $message = "The {$entityName} has been successfully created";

        // Apply field setter callback
        $fieldSetter($model);

        // Set timestamps
        $model->created_at = now();
        $model->updated_at = now();

        // Attempt save
        $saved = $model->save();

        // Update response if save failed
        if (!$saved) {
            $icon = 'error';
            $message = "The {$entityName} could not be created";
        }

        return [
            'icon' => $icon,
            'message' => $message,
            'response' => $saved,
        ];
    }
}
