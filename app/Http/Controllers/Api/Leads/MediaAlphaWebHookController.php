<?php

namespace App\Http\Controllers\Api\Leads;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Services\Leads\MediaAlphaWebhookService;

class MediaAlphaWebHookController extends Controller
{
    /**
     * Webhook to receive ping response.
     */
    public function pingResponse(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string',
                'placement_id' => 'required|string',
                'leadid_id' => 'nullable|string',
                'response' => 'required|array',
                'is_error' => 'boolean',
            ]);

            $response = MediaAlphaWebhookService::storePingResponse(
                $validated['phone'],
                $validated['placement_id'],
                $validated['response'],
                $validated['is_error'] ?? false,
                $validated['leadid_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Ping response stored successfully',
                'data' => [
                    'phone' => $response->phone,
                    'ping_status' => $response->ping_status,
                    'ping_id' => $response->ping_id,
                    'total_buyers' => $response->total_buyers,
                    'highest_bid' => $response->highest_bid,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook to receive post response.
     */
    public function postResponse(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string',
                'response' => 'required|array',
                'is_error' => 'boolean',
            ]);

            $response = MediaAlphaWebhookService::storePostResponse(
                $validated['phone'],
                $validated['response'],
                $validated['is_error'] ?? false
            );

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'message' => 'No ping record found for this phone number',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post response stored successfully',
                'data' => [
                    'phone' => $response->phone,
                    'post_status' => $response->post_status,
                    'post_revenue' => $response->post_revenue,
                    'winning_buyer' => $response->winning_buyer,
                    'accepted_buyers' => $response->accepted_buyers,
                    'final_status' => $response->status,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook to receive complete response (ping + post).
     */
    public function completeResponse(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string',
                'placement_id' => 'required|string',
                'leadid_id' => 'nullable|string',
                'ping_response' => 'required|array',
                'post_response' => 'required|array',
                'ping_error' => 'boolean',
                'post_error' => 'boolean',
            ]);

            $response = MediaAlphaWebhookService::storeCompleteResponse(
                $validated['phone'],
                $validated['placement_id'],
                $validated['ping_response'],
                $validated['post_response'],
                $validated['ping_error'] ?? false,
                $validated['post_error'] ?? false,
                $validated['leadid_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Complete response stored successfully',
                'data' => [
                    'phone' => $response->phone,
                    'ping_status' => $response->ping_status,
                    'post_status' => $response->post_status,
                    'final_status' => $response->status,
                    'revenue' => $response->post_revenue,
                    'total_buyers' => $response->total_buyers,
                    'winning_buyer' => $response->winning_buyer,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick stats for dashboard.
     */
    public function quickStats(): JsonResponse
    {
        try {
            $stats = MediaAlphaWebhookService::getQuickStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving quick stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Top buyers by revenue.
     */
    public function topBuyers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'limit' => 'sometimes|integer|min:1|max:50',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
            ]);

            $topBuyers = MediaAlphaWebhookService::getTopBuyers(
                $validated['limit'] ?? 10,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $topBuyers,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving top buyers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Performance by placement.
     */
    public function placementPerformance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
            ]);

            $performance = MediaAlphaWebhookService::getPlacementPerformance(
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $performance,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving placement performance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
