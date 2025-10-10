<?php

namespace App\Pipelines\MediaAlpha\Steps;

use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Leads\MediaAlphaConfig;
use App\Pipelines\MediaAlpha\MediaAlphaLeadContext;
use App\Pipelines\MediaAlpha\Support\RequestHelper;

class PostStep
{
    use RequestHelper;

    public function handle(MediaAlphaLeadContext $context, Closure $next)
    {
        if (!$context->hasValidBuyersForPost) {
            Log::info('Skipping Post step - no valid buyers', ['phone' => $context->phone]);

            return $next($context);
        }

        $startTime = microtime(true);

        try {
            $config = MediaAlphaConfig::active()->byPlacementId($context->placementId)->firstOrFail();
            $selectedBuyer = $this->selectHighestBidBuyer($context->persistenceData->ping_raw_response);

            if (!$selectedBuyer) {
                Log::info('No highest bidder found for Post', ['phone' => $context->phone]);

                return $next($context);
            }

            $postData = $this->preparePayload($context->leadData, $config, $selectedBuyer, $context->persistenceData->ping_raw_response);
            $postUrl = $config->getPostUrlAttribute();
            $requestInfo = $this->buildRequestInfo($postUrl, $postData, $selectedBuyer);

            Log::info('Starting Post', ['phone' => $context->phone, 'buyer' => $selectedBuyer['buyer_id'] ?? 'unknown']);

            $response = $this->sendRequest($postUrl, $postData);
            $responseTime = $this->measureResponseTime($startTime);
            $responseData = $this->parseResponse($response, $responseTime);

            if ($response->successful()) {
                $context->updatePostData($responseData, false, $requestInfo);
            } else {
                $context->updatePostData($responseData, true, $requestInfo);
                $context->addError('post', 'HTTP failed: ' . $response->status());
            }
        } catch (Exception $e) {
            $responseTime = $this->measureResponseTime($startTime);
            $context->updatePostData(['error' => $e->getMessage(), 'response_time_ms' => $responseTime], true);
            $context->addError('post', $e->getMessage());

            Log::error('Post Exception', [
                'phone' => $context->phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($context);
    }
}
