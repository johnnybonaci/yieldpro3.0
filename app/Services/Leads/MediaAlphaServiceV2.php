<?php

namespace App\Services\Leads;

use App\Pipelines\MediaAlpha\MediaAlphaPipeline;

class MediaAlphaServiceV2
{
    private MediaAlphaPipeline $pipeline;

    public function __construct(MediaAlphaPipeline $pipeline)
    {
        $this->pipeline = $pipeline ?? new MediaAlphaPipeline();
    }

    public function submitLead(array $leadData, string $placementId): array
    {
        return $this->pipeline->process($leadData, $placementId);
    }
}
