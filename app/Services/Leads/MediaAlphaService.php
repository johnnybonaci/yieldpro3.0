<?php

namespace App\Services\Leads;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Leads\MediaAlphaConfig;

class MediaAlphaService
{
    private $config;

    private $userAgent;

    private $ipAddress;

    public function __construct(MediaAlphaConfig $config)
    {
        $this->config = $config;
        $this->userAgent = request()->header('User-Agent') ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $this->ipAddress = request()->ip() ?? '127.0.0.1';
    }

    /**
     * Create service instance by placement_id.
     */
    public static function byPlacementId(string $placementId): self
    {
        $config = MediaAlphaConfig::active()
            ->byPlacementId($placementId)
            ->firstOrFail();

        return new self($config);
    }

    /**
     * Perform ping (pre-lead submission verification).
     */
    public function ping(array $leadData): array
    {
        $pingData = $this->preparePingData($leadData);
        $phone = $leadData['phone'] ?? null;
        $pingUrl = $this->config->getPingUrlAttribute();

        // Preparar información del request para logging
        $requestInfo = [
            'url' => $pingUrl,
            'method' => 'POST',
            'headers' => ['Content-Type: application/json'],
            'body' => $pingData,
            'timestamp' => now()->toISOString(),
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)->post($pingUrl, $pingData);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 3);

            $responseData = $response->json();

            return [
                'success' => $response->successful(),
                'data' => $responseData,
                'ping_data' => $pingData,
                'http_status' => $response->status(),
                'response_time_ms' => $responseTime,
                'request_info' => $requestInfo,
            ];
        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 3);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'ping_data' => $pingData,
                'response_time_ms' => $responseTime,
                'request_info' => $requestInfo,
            ];
        }
    }

    /**
     * Select the buyer with the highest bid from ping response.
     */
    private function selectHighestBidBuyer(array $pingResponse): ?array
    {
        if (!isset($pingResponse['buyers']) || !is_array($pingResponse['buyers']) || empty($pingResponse['buyers'])) {
            Log::warning('No buyers found in ping response', ['ping_response' => $pingResponse]);

            return null;
        }

        $buyers = $pingResponse['buyers'];
        $highestBidBuyer = null;
        $highestBid = 0;

        foreach ($buyers as $buyer) {
            if (!isset($buyer['bid']) || !is_numeric($buyer['bid'])) {
                Log::debug('Buyer without valid bid', ['buyer' => $buyer]);

                continue;
            }

            $currentBid = floatval($buyer['bid']);

            if (isset($buyer['status'])) {
                $buyerStatus = $buyer['status'];
                if (!in_array($buyerStatus, ['accepted', 'success', 'approved'])) {
                    Log::debug('Buyer with invalid status', [
                        'buyer_id' => $buyer['buyer_id'] ?? $buyer['buyer'] ?? 'unknown',
                        'status' => $buyerStatus,
                        'bid' => $currentBid,
                    ]);

                    continue;
                }
            }

            if ($currentBid <= 0) {
                Log::debug('Buyer with zero or negative bid', [
                    'buyer' => $buyer['buyer'] ?? 'unknown',
                    'bid' => $currentBid,
                ]);

                continue;
            }

            if ($currentBid > $highestBid) {
                $highestBid = $currentBid;
                $highestBidBuyer = $buyer;
            }
        }

        return $highestBidBuyer;
    }

    /**
     * Perform post (lead submission).
     */
    public function post(array $leadData, array $pingResponse = []): array
    {
        $selectedBuyer = $this->selectHighestBidBuyer($pingResponse);

        if (!$selectedBuyer) {
            return [
                'success' => false,
                'error' => 'No buyers available or no valid bids found',
                'ping_response' => $pingResponse,
            ];
        }

        $postData = $this->preparePostData($leadData, $pingResponse, $selectedBuyer);
        $phone = $leadData['phone'] ?? null;
        $postUrl = $this->config->getPostUrlAttribute();

        // Preparar información del request
        $requestInfo = [
            'url' => $postUrl,
            'method' => 'POST',
            'headers' => ['Content-Type: application/json'],
            'body' => $postData,
            'selected_buyer' => $selectedBuyer,
            'timestamp' => now()->toISOString(),
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)->post($postUrl, $postData);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 3);

            $responseData = $response->json();

            return [
                'success' => $response->successful(),
                'data' => $responseData,
                'post_data' => $postData,
                'selected_buyer' => $selectedBuyer,
                'response_time_ms' => $responseTime,
                'request_info' => $requestInfo,
            ];
        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 3);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'post_data' => $postData,
                'response_time_ms' => $responseTime,
                'request_info' => $requestInfo,
            ];
        }
    }

    /**
     * Full process: ping + post.
     */
    public function submitLead(array $leadData): array
    {
        $phone = $leadData['phone'] ?? null;

        // First perform ping
        $pingResult = $this->ping($leadData);

        // Si el ping no fue exitoso, retornar solo el resultado del ping
        if (!$pingResult['success']) {
            return [
                'success' => false,
                'ping_result' => $pingResult,
                'post_result' => null,
                'reason' => 'Ping failed or returned bad status',
            ];
        }

        // Verificar si hay buyers válidos antes de hacer post
        $selectedBuyer = $this->selectHighestBidBuyer($pingResult['data']);

        if (!$selectedBuyer) {
            return [
                'success' => false,
                'ping_result' => $pingResult,
                'post_result' => null,
                'reason' => 'No valid buyers found in ping response',
            ];
        }

        // Si hay buyers válidos, proceder con el post
        $postResult = $this->post($leadData, $pingResult['data']);

        return [
            'success' => $postResult['success'],
            'ping_result' => $pingResult,
            'post_result' => $postResult,
        ];
    }

    /**
     * Prepare ping data.
     */
    private function preparePingData(array $leadData): array
    {
        return [
            'api_token' => $this->config->api_token,
            'placement_id' => $this->config->placement_id,
            'version' => $this->config->version,
            'ua' => $this->userAgent,
            'ip' => $this->ipAddress,
            'url' => $this->config->source_url,
            'date_time' => now()->format('Y-m-d H:i:s'),
            'data' => $this->mapLeadData($leadData),
        ];
    }

    /**
     * Prepare post data.
     */
    private function preparePostData(array $leadData, array $pingResponse = [], ?array $selectedBuyer = null): array
    {
        $postData = [
            'api_token' => $this->config->api_token,
            'placement_id' => $this->config->placement_id,
            'version' => $this->config->version,
            'ua' => $this->userAgent,
            'ip' => $this->ipAddress,
            'url' => $this->config->source_url,
            'date_time' => now()->format('Y-m-d H:i:s'),
            'data' => $this->mapLeadData($leadData),
        ];

        if ($pingResponse && isset($pingResponse['ping_id'])) {
            $postData['ping_id'] = $pingResponse['ping_id'];
        }

        if ($selectedBuyer) {
            $postData['bid_id'] = $selectedBuyer['bid_id'] ?? null;

            if (isset($selectedBuyer['buyer_id'])) {
                $postData['buyer_id'] = $selectedBuyer['buyer_id'];
            }

            $postData['selected_bid'] = $selectedBuyer['bid'] ?? null;

            if (isset($selectedBuyer['buyer_token'])) {
                $postData['buyer_token'] = $selectedBuyer['buyer_token'];
            }
        }

        return $postData;
    }

    /**
     * Map lead data using configuration.
     */
    private function mapLeadData(array $leadData): array
    {
        $mappedData = $leadData;

        if (!isset($mappedData['tcpa'])) {
            $mappedData['tcpa'] = $this->config->getDefaultTcpaConfig();
        }

        return $mappedData;
    }

    /**
     * Get current configuration.
     */
    public function getConfig(): MediaAlphaConfig
    {
        return $this->config;
    }
}
