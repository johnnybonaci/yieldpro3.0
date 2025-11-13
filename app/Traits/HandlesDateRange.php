<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Trait for handling date range extraction from requests.
 * Eliminates duplicate date handling code across controllers.
 *
 * SonarCube Quality: Reduces ~51 lines of duplicated code (17 occurrences × 3 lines).
 */
trait HandlesDateRange
{
    /**
     * Extract date range from request with defaults.
     *
     * @param Request $request
     * @return array Contains: date_start, date_end, newstart, newend
     */
    protected function getDateRange(Request $request): array
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));

        // Extract additional date range variables using helper function
        extract(__toRangePassDay($date_start, $date_end));

        return compact('date_start', 'date_end', 'newstart', 'newend');
    }

    /**
     * Get pagination parameters from request.
     *
     * @param Request $request
     * @return array Contains: page, size
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'page' => $request->get('page', 1),
            'size' => $request->get('size', 20),
        ];
    }
}
