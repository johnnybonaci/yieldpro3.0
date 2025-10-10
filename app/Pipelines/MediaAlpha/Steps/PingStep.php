<?php

namespace App\Pipelines\MediaAlpha\Steps;

use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Leads\MediaAlphaConfig;
use App\Pipelines\MediaAlpha\MediaAlphaLeadContext;
use App\Pipelines\MediaAlpha\Support\RequestHelper;

class PingStep
{
    use RequestHelper;

    public function handle(MediaAlphaLeadContext $context, Closure $next)
    {
        $startTime = microtime(true);

        try {
            $config = MediaAlphaConfig::active()->byPlacementId($context->placementId)->firstOrFail();
            $pingData = $this->preparePayload($context->leadData, $config);
            $pingUrl = $config->getPingUrlAttribute();
            $requestInfo = $this->buildRequestInfo($pingUrl, $pingData);

            Log::info('MediaAlpha Pipeline - Starting Ping', ['phone' => $context->phone]);

            $response = $this->sendRequest($pingUrl, $pingData);
            $responseTime = $this->measureResponseTime($startTime);
            $responseData = $this->parseResponse($response, $responseTime);

            if ($response->successful()) {
                $context->updatePingData($responseData, false, $requestInfo);
            } else {
                $context->updatePingData($responseData, true, $requestInfo);
                $context->addError('ping', 'HTTP failed: ' . $response->status());
            }
        } catch (Exception $e) {
            $responseTime = $this->measureResponseTime($startTime);
            $context->updatePingData(['error' => $e->getMessage(), 'response_time_ms' => $responseTime], true);
            $context->addError('ping', $e->getMessage());

            Log::error('Ping Exception', [
                'phone' => $context->phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($context);
    }
}
