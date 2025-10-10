<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Leads\MediaAlphaConfig;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Validation\ValidationException;

class MediaAlphaConfigController extends Controller
{
    /**
     * List all configurations.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        return MediaAlphaConfig::filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Show specific configuration.
     */
    public function show(MediaAlphaConfig $config): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Create new configuration.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:media_alpha_configs',
                'api_token' => 'required|string',
                'placement_id' => 'required|string|unique:media_alpha_configs',
                'version' => 'sometimes|integer|min:1',
                'base_url' => 'sometimes|url',
                'ping_endpoint' => 'sometimes|string',
                'post_endpoint' => 'sometimes|string',
                'source_url' => 'required|url',
                'tcpa_config' => 'sometimes|array',
                'tcpa_config.call_consent' => 'sometimes|boolean',
                'tcpa_config.email_consent' => 'sometimes|boolean',
                'tcpa_config.sms_consent' => 'sometimes|boolean',
                'tcpa_config.text' => 'sometimes|string',
                'tcpa_config.url' => 'sometimes|url',
                'default_mapping' => 'sometimes|array',
                'active' => 'sometimes|boolean',
            ]);

            $config = MediaAlphaConfig::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuration created successfully',
                'data' => $config,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update configuration.
     */
    public function update(Request $request, MediaAlphaConfig $config): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:media_alpha_configs,name,' . $config->id,
                'api_token' => 'sometimes|string',
                'placement_id' => 'sometimes|string|unique:media_alpha_configs,placement_id,' . $config->id,
                'version' => 'sometimes|integer|min:1',
                'base_url' => 'sometimes|url',
                'ping_endpoint' => 'sometimes|string',
                'post_endpoint' => 'sometimes|string',
                'source_url' => 'sometimes|url',
                'tcpa_config' => 'sometimes|array',
                'tcpa_config.call_consent' => 'sometimes|boolean',
                'tcpa_config.email_consent' => 'sometimes|boolean',
                'tcpa_config.sms_consent' => 'sometimes|boolean',
                'tcpa_config.text' => 'sometimes|string',
                'tcpa_config.url' => 'sometimes|url',
                'default_mapping' => 'sometimes|array',
                'active' => 'sometimes|boolean',
            ]);

            $config->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $config->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Delete configuration.
     */
    public function destroy(MediaAlphaConfig $config): JsonResponse
    {
        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuration deleted successfully',
        ]);
    }

    /**
     * Toggle configuration active state.
     */
    public function toggleActive(MediaAlphaConfig $config): JsonResponse
    {
        $config->update(['active' => !$config->active]);

        return response()->json([
            'success' => true,
            'message' => 'Configuration active state updated',
            'data' => $config->fresh(),
        ]);
    }

    /**
     * Get all active configurations.
     */
    public function active(): JsonResponse
    {
        $configs = MediaAlphaConfig::active()->get();

        return response()->json([
            'success' => true,
            'data' => $configs,
        ]);
    }

    /**
     * Get configuration by placement_id.
     */
    public function getByPlacementId(string $placementId): JsonResponse
    {
        $config = MediaAlphaConfig::active()
            ->byPlacementId($placementId)
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found for the provided placement_id',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }
}
