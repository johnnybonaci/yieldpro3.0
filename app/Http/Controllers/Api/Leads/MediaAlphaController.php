<?php

namespace App\Http\Controllers\Api\Leads;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class MediaAlphaController extends Controller
{
    private const API_TOKEN = 'Mp7Z-Vx85CJlRo9_iKWWaHxNu6w0Q4fI';

    private const PLACEMENT_ID = 'tv81tqxv-bBZTE7zPKfZZFTwDOgPUw';

    private const VERSION = 18;

    private const PING_URL = 'https://insurance-leads.mediaalpha.com/ping.json';

    private const POST_URL = 'https://insurance-leads.mediaalpha.com/post.json';

    public function submit(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'zip' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos requeridos faltantes',
                    'details' => $validator->errors(),
                ], 400);
            }

            $formData = $request->all();

            Log::info('MediaAlpha: Iniciando PING', ['form_data' => $formData]);

            $pingPayload = $this->mapFormDataToPing($formData);
            $pingResponse = $this->makePingRequest($pingPayload);

            if (!$pingResponse['success']) {
                return response()->json($pingResponse, $pingResponse['status'] ?? 500);
            }

            Log::info('MediaAlpha: Iniciando POST', [
                'ping_id' => $pingResponse['data']['ping_id'],
                'bid_ids' => $pingResponse['data']['bid_ids'],
            ]);

            $postPayload = $this->mapFormDataToPost(
                $formData,
                $pingResponse['data']['ping_id'],
                $pingResponse['data']['bid_ids']
            );

            $postResponse = $this->makePostRequest($postPayload);

            if (!$postResponse['success']) {
                return response()->json($postResponse, $postResponse['status'] ?? 500);
            }

            $finalResponse = [
                'success' => true,
                'message' => 'Lead enviado exitosamente a Media Alpha',
                'ping' => $pingResponse['data'],
                'post' => $postResponse['data'],
            ];

            Log::info('MediaAlpha: Flujo completo exitoso', $finalResponse);

            return response()->json($finalResponse);
        } catch (Exception $e) {
            Log::error('MediaAlpha: Error en flujo completo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function makePingRequest(array $payload): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post(self::PING_URL, $payload);

            if (!$response->successful()) {
                Log::error('MediaAlpha PING Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Error en PING a Media Alpha',
                    'status' => $response->status(),
                    'message' => $response->body(),
                ];
            }

            $responseData = $response->json();

            if (!isset($responseData['ping_id'])) {
                Log::error('MediaAlpha PING: Missing ping_id', $responseData);

                return [
                    'success' => false,
                    'error' => 'Respuesta inválida del PING: falta ping_id',
                ];
            }

            if (!isset($responseData['bid_ids']) || !is_array($responseData['bid_ids']) || empty($responseData['bid_ids'])) {
                Log::error('MediaAlpha PING: Invalid bid_ids', $responseData);

                return [
                    'success' => false,
                    'error' => 'Respuesta inválida del PING: bid_ids faltantes o inválidos',
                ];
            }

            Log::info('MediaAlpha PING Success', $responseData);

            return [
                'success' => true,
                'data' => $responseData,
            ];
        } catch (Exception $e) {
            Log::error('MediaAlpha PING Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error en PING: ' . $e->getMessage(),
            ];
        }
    }

    private function makePostRequest(array $payload): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post(self::POST_URL, $payload);

            if (!$response->successful()) {
                Log::error('MediaAlpha POST Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Error en POST a Media Alpha',
                    'status' => $response->status(),
                    'message' => $response->body(),
                ];
            }

            $responseData = $response->json();

            Log::info('MediaAlpha POST Success', $responseData);

            return [
                'success' => true,
                'data' => $responseData,
            ];
        } catch (Exception $e) {
            Log::error('MediaAlpha POST Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error en POST: ' . $e->getMessage(),
            ];
        }
    }

    private function mapFormDataToPing(array $formData): array
    {
        return [
            'api_token' => self::API_TOKEN,
            'placement_id' => self::PLACEMENT_ID,
            'version' => self::VERSION,
            'data' => $this->mapFormData($formData),
        ];
    }

    private function mapFormDataToPost(array $formData, string $pingId, array $bidIds): array
    {
        return [
            'api_token' => self::API_TOKEN,
            'placement_id' => self::PLACEMENT_ID,
            'version' => self::VERSION,
            'ping_id' => $pingId,
            'bid_ids' => $bidIds,
            'data' => $this->mapFormData($formData),
        ];
    }

    private function mapFormData(array $formData): array
    {
        $fullname = $formData['fullname'] ?? null;
        if (!$fullname) {
            if (isset($formData['first_name']) && isset($formData['last_name'])) {
                $fullname = $formData['first_name'] . ' ' . $formData['last_name'];
            } elseif (isset($formData['first_Name']) && isset($formData['last_Name'])) {
                $fullname = $formData['first_Name'] . ' ' . $formData['last_Name'];
            }
        }

        $dob = null;
        if (isset($formData['birthDate'])) {
            if (is_array($formData['birthDate'])) {
                $dob = $formData['birthDate']['month'] . '/'
                    . $formData['birthDate']['day'] . '/'
                    . $formData['birthDate']['year'];
            } else {
                $dob = $formData['birthDate'];
            }
        } elseif (isset($formData['dob'])) {
            $dob = $formData['dob'];
        }

        return [
            'zip' => $formData['zip'] ?? $formData['zip_code'] ?? $formData['zipcode'] ?? null,
            'county' => $formData['city'] ?? null,
            'contact' => $fullname,
            'email' => $formData['email'] ?? null,
            'phone' => $formData['ma_phone'] ?? $formData['phone'] ?? null,
            'address' => $formData['address'] ?? null,
            'primary_language' => 'English',
            'primary.name' => $fullname,
            'primary.gender' => $formData['gender'] ?? null,
            'primary.birth_date' => $dob,
            'household_income' => $formData['household_income'] ?? null,
            'household_size' => $formData['household_size'] ?? null,
            'leadid_id' => $formData['universal_leadid'] ?? null,
            'tcpa.call_consent' => 1,
            'tcpa.email_consent' => 1,
        ];
    }
}
