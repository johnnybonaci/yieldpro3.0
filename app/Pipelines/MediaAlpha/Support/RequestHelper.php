<?php

namespace App\Pipelines\MediaAlpha\Support;

use Illuminate\Support\Facades\Http;

trait RequestHelper
{
    private string $userAgent;

    private string $ipAddress;

    public function __construct()
    {
        $this->userAgent = request()->header('User-Agent', 'Mozilla/5.0');
        $this->ipAddress = request()->ip() ?? '127.0.0.1';
    }

    protected function preparePayload(array $leadData, $config, ?array $selectedBuyer = null, ?array $pingResponse = null): array
    {
        $payload = [
            'api_token' => $config->api_token,
            'placement_id' => $config->placement_id,
            'version' => $config->version,
            'ua' => $this->userAgent,
            'ip' => $this->ipAddress,
            'url' => $config->source_url,
            'date_time' => now()->format('Y-m-d H:i:s'),
            'data' => $leadData,
        ];

        if (!isset($payload['data']['tcpa'])) {
            $payload['data']['tcpa'] = $config->getDefaultTcpaConfig();
        }

        if ($pingResponse['ping_id'] ?? false) {
            $payload['ping_id'] = $pingResponse['ping_id'];
        }

        if ($selectedBuyer) {
            $payload['bid_ids'] = [
                $selectedBuyer['bid_id'],
            ];
        }

        return $payload;
    }

    protected function buildRequestInfo(string $url, array $body, ?array $selectedBuyer = null): array
    {
        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'selected_buyer' => $selectedBuyer,
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function measureResponseTime(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 3);
    }

    protected function sendRequest(string $url, array $payload)
    {
        return Http::timeout(30)->post($url, $payload);
    }

    protected function parseResponse($response, float $responseTime): array
    {
        $data = $response->json() ?? [];
        $data['response_time_ms'] = $responseTime;

        return $data;
    }

    protected function selectHighestBidBuyer(array $pingResponse): ?array
    {
        if (empty($pingResponse['buyers'])) {
            return null;
        }

        return collect($pingResponse['buyers'])
            ->filter(fn ($b) => ($b['bid'] ?? 0) > 0)
            ->sortByDesc('bid')
            ->first();
    }
}
