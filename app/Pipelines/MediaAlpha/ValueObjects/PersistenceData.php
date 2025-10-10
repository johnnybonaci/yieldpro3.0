<?php

namespace App\Pipelines\MediaAlpha\ValueObjects;

use App\Pipelines\MediaAlpha\Enums\LeadStatus;

class PersistenceData
{
    public ?string $phone_id;

    public string $placement_id;

    public ?string $leadid_id;

    public LeadStatus $status;

    public string $date_history;

    public ?string $ping_id = null;

    public ?array $ping_buyers = null;

    public ?string $ping_status = null;

    public ?string $ping_error = null;

    public ?array $ping_raw_response = null;

    public ?array $ping_request_data = null;

    public ?float $ping_time = null;

    public ?array $post_buyers = null;

    public ?string $post_status = null;

    public ?string $post_error = null;

    public ?array $post_raw_response = null;

    public ?array $post_request_data = null;

    public ?float $post_time = null;

    public ?float $post_revenue = null;

    public ?string $winning_buyer = null;

    public int $total_buyers = 0;

    public int $accepted_buyers = 0;

    public int $rejected_buyers = 0;

    public ?float $highest_bid = null;

    public function __construct(?string $phoneId, string $placementId, ?string $leadidId)
    {
        $this->phone_id = $phoneId;
        $this->placement_id = $placementId;
        $this->leadid_id = $leadidId;
        $this->status = LeadStatus::PROCESSING;
        $this->date_history = now()->format('Y-m-d');
    }

    public function updatePing(array $response, bool $isError, array $requestInfo): void
    {
        $this->ping_raw_response = $response;
        $this->ping_request_data = $requestInfo;
        $this->ping_time = $response['response_time_ms'] ?? null;
        $this->ping_status = $isError ? 'error' : 'success';
        $this->ping_error = $isError ? ($response['error'] ?? 'Unknown') : null;
        $this->status = $isError ? LeadStatus::FAILED : $this->status;

        if (!$isError && isset($response['buyers'])) {
            $this->ping_id = $response['ping_id'] ?? null;
            $this->ping_buyers = $response['buyers'];
            $this->total_buyers = count($response['buyers']);
            $this->accepted_buyers = collect($response['buyers'])->filter(fn ($b) => ($b['bid'] ?? 0) > 0)->count();
            $this->rejected_buyers = $this->total_buyers - $this->accepted_buyers;
            $this->highest_bid = collect($response['buyers'])->pluck('bid')->max();
        }
    }

    public function updatePost(array $response, bool $isError, array $requestInfo): void
    {
        $this->post_raw_response = $response;
        $this->post_request_data = $requestInfo;
        $this->post_time = $response['response_time_ms'] ?? null;
        $this->post_status = $isError ? 'failed' : ($response['status'] ?? 'succeeded');
        $this->post_error = $isError ? ($response['error'] ?? 'Unknown') : null;

        if (!$isError) {
            $this->post_buyers = $response['buyers'] ?? null;
            $this->post_revenue = $response['rev'] ?? $response['revenue'] ?? 0;
            $this->winning_buyer = $requestInfo['selected_buyer']['buyer'] ?? null;
            $this->status = $this->post_revenue > 0 ? LeadStatus::COMPLETED : LeadStatus::COMPLETED_NO_REVENUE;
        } else {
            $this->status = LeadStatus::FAILED;
        }
    }

    public function finalize(bool $pingSuccess, bool $hasValidBuyers): void
    {
        if ($pingSuccess && !$hasValidBuyers) {
            $this->status = LeadStatus::COMPLETED_NO_BUYERS;
            $this->post_status = 'skipped';
        }
        if (!$pingSuccess) {
            $this->status = LeadStatus::FAILED;
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
