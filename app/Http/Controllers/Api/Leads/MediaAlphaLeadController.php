<?php

namespace App\Http\Controllers\Api\Leads;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Leads\MediaAlphaService;
use App\Services\Leads\MediaAlphaServiceV2;
use Illuminate\Validation\ValidationException;

class MediaAlphaLeadController extends Controller
{
    public const VALIDATION_E = 'Validation error';

    public const INTERNAL_SERVER_E = 'Internal server error';

    public const RULE_SOMETIMES_BOOLEAN = 'sometimes|boolean';

    public const RULE_SOMETIMES_NUMERIC_MIN_0 = 'sometimes|numeric|min:0';

    public const RULE_SOMETIMES_ARRAY = 'sometimes|array';

    public MediaAlphaServiceV2 $service2;

    public function __construct(MediaAlphaServiceV2 $service2)
    {
        $this->service2 = $service2;
    }

    /**
     * Perform ping for a lead.
     */
    public function ping(Request $request, string $placementId): JsonResponse
    {
        try {
            $validated = $this->validateLeadData($request);

            $service = MediaAlphaService::byPlacementId($placementId);
            $result = $service->ping($validated);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => self::VALIDATION_E,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => self::INTERNAL_SERVER_E,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform post for a lead.
     */
    public function post(Request $request, string $placementId): JsonResponse
    {
        try {
            $validated = $this->validateLeadData($request);
            $pingResponse = $request->input('ping_response', null);

            $service = MediaAlphaService::byPlacementId($placementId);
            $result = $service->post($validated, $pingResponse);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => self::VALIDATION_E,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => self::INTERNAL_SERVER_E,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process full lead (ping + post).
     */
    public function submit(Request $request, string $placementId): JsonResponse
    {
        try {
            $validated = $this->validateLeadData($request);

            $result = $this->service2->submitLead($validated, $placementId);

            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => self::VALIDATION_E,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => self::INTERNAL_SERVER_E,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate lead data.
     */
    private function validateLeadData(Request $request): array
    {
        return $request->validate([
            'contact' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'address_2' => 'sometimes|string|max:255',
            'zip' => 'required|string|max:10',
            'county' => 'sometimes|string|max:100',

            'currently_insured' => self::RULE_SOMETIMES_BOOLEAN,
            'lost_coverage' => self::RULE_SOMETIMES_BOOLEAN,
            'household_income' => self::RULE_SOMETIMES_NUMERIC_MIN_0,
            'primary_language' => 'sometimes|string|max:50',

            'primary' => self::RULE_SOMETIMES_ARRAY,
            'primary.name' => 'sometimes|string|max:255',
            'primary.birth_date' => 'sometimes|date',
            'primary.gender' => 'sometimes|string|in:M,F,Male,Female',
            'primary.height' => self::RULE_SOMETIMES_NUMERIC_MIN_0,
            'primary.weight' => self::RULE_SOMETIMES_NUMERIC_MIN_0,

            'tcpa' => self::RULE_SOMETIMES_ARRAY,
            'tcpa.call_consent' => self::RULE_SOMETIMES_BOOLEAN,
            'tcpa.email_consent' => self::RULE_SOMETIMES_BOOLEAN,
            'tcpa.sms_consent' => self::RULE_SOMETIMES_BOOLEAN,
            'tcpa.text' => 'sometimes|string',
            'tcpa.url' => 'sometimes|url',

            'leadid_id' => 'sometimes|string|max:50',

            'custom_fields' => self::RULE_SOMETIMES_ARRAY,
        ]);
    }
}
