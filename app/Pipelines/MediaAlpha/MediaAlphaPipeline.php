<?php

namespace App\Pipelines\MediaAlpha;

use Exception;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use App\Pipelines\MediaAlpha\Steps\PingStep;
use App\Pipelines\MediaAlpha\Steps\PostStep;
use App\Pipelines\MediaAlpha\Steps\DatabasePersistenceStep;

class MediaAlphaPipeline
{
    public function process(array $leadData, string $placementId): array
    {
        $context = new MediaAlphaLeadContext($leadData, $placementId);

        Log::info('Pipeline started', [
            'phone' => $context->phone,
            'placement_id' => $placementId,
        ]);

        $steps = [
            PingStep::class,
            PostStep::class,
            DatabasePersistenceStep::class,
        ];

        try {
            $result = app(Pipeline::class)
                ->send($context)
                ->through($steps)
                ->thenReturn();

            Log::info('Pipeline completed', [
                'phone' => $result->phone,
                'success' => $result->getOverallSuccess(),
                'errors' => $result->errors,
            ]);

            return [
                'success' => $result->getOverallSuccess(),
                'ping_success' => $result->pingSuccess,
                'post_success' => $result->postSuccess,
                'has_valid_buyers' => $result->hasValidBuyersForPost,
                'errors' => $result->errors,
                'persistence_data' => $result->persistenceData->toArray(),
            ];
        } catch (Exception $e) {
            Log::error('Pipeline fatal error', [
                'phone' => $context->phone,
                'error' => $e->getMessage(),
            ]);

            $context->addError('pipeline', $e->getMessage());

            return [
                'success' => false,
                'ping_success' => $context->pingSuccess,
                'post_success' => $context->postSuccess,
                'has_valid_buyers' => $context->hasValidBuyersForPost,
                'errors' => $context->errors,
                'persistence_data' => $context->persistenceData->toArray(),
            ];
        }
    }
}
