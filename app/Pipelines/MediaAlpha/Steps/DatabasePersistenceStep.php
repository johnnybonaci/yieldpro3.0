<?php

namespace App\Pipelines\MediaAlpha\Steps;

use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Leads\MediaAlphaResponse;
use App\Pipelines\MediaAlpha\MediaAlphaLeadContext;

class DatabasePersistenceStep
{
    public function handle(MediaAlphaLeadContext $context, Closure $next)
    {
        try {
            $context->finalizeForPersistence();

            if (!$context->phone) {
                Log::warning('Cannot persist: phone missing');

                return $next($context);
            }

            MediaAlphaResponse::create($context->persistenceData->toArray());

            Log::info('Data persisted successfully', [
                'phone' => $context->phone,
                'status' => $context->persistenceData->status->value,
                'revenue' => $context->persistenceData->post_revenue ?? 0,
            ]);
        } catch (Exception $e) {
            Log::error('Persistence failed', [
                'phone' => $context->phone,
                'error' => $e->getMessage(),
            ]);

            $context->addError('persistence', $e->getMessage());
        }

        return $next($context);
    }
}
