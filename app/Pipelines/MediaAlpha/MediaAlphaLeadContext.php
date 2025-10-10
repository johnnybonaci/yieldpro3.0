<?php

namespace App\Pipelines\MediaAlpha;

use App\Pipelines\MediaAlpha\ValueObjects\PersistenceData;

class MediaAlphaLeadContext
{
    public array $leadData;

    public string $phone;

    public string $placementId;

    public ?string $leadidId;

    public PersistenceData $persistenceData;

    public bool $hasValidBuyersForPost = false;

    public bool $pingSuccess = false;

    public bool $postSuccess = false;

    public array $errors = [];

    public function __construct(array $leadData, string $placementId)
    {
        $this->leadData = $leadData;
        $this->phone = $leadData['phone'] ?? '';
        $this->placementId = $placementId;
        $this->leadidId = $leadData['leadid_id'] ?? null;

        $this->initializePersistenceData();
    }

    private function initializePersistenceData(): void
    {
        $cleanPhone = $this->cleanPhone($this->phone);
        $this->persistenceData = new PersistenceData(
            $cleanPhone,
            $this->placementId,
            $this->leadidId
        );
    }

    public function updatePingData(array $response, bool $isError, array $requestInfo = []): void
    {
        $this->pingSuccess = !$isError;
        $this->hasValidBuyersForPost = false;

        $this->persistenceData->updatePing($response, $isError, $requestInfo);

        if ($this->pingSuccess) {
            $this->hasValidBuyersForPost = $this->persistenceData->accepted_buyers > 0;
        }
    }

    public function updatePostData(array $response, bool $isError, array $requestInfo = []): void
    {
        $this->postSuccess = !$isError;
        $this->persistenceData->updatePost($response, $isError, $requestInfo);
    }

    public function finalizeForPersistence(): void
    {
        $this->persistenceData->finalize($this->pingSuccess, $this->hasValidBuyersForPost);
    }

    public function addError(string $stage, string $error): void
    {
        $this->errors[$stage] = $error;
    }

    public function getOverallSuccess(): bool
    {
        return $this->pingSuccess && (!$this->hasValidBuyersForPost || $this->postSuccess);
    }

    private function cleanPhone(string $phone): ?string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (!$cleaned) {
            return null;
        }

        if (strlen($cleaned) === 11 && $cleaned[0] === '1') {
            $cleaned = substr($cleaned, 1);
        }

        if (strlen($cleaned) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6, 4)
            );
        }

        return null;
    }
}
