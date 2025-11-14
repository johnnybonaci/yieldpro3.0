<?php

namespace App\Traits;

/**
 * MediaAlpha Analytics Trait
 *
 * Extracts analytics methods from MediaAlphaResponse model
 * to reduce method count and improve SonarCube compliance.
 */
trait MediaAlphaAnalytics
{
    /**
     * Get detailed buyer information from both ping and post responses.
     */
    public function getBuyerDetails(): array
    {
        $details = [];

        if ($this->ping_buyers) {
            foreach ($this->ping_buyers as $buyer) {
                $buyerId = $buyer['buyer_id'] ?? $buyer['buyer'] ?? 'unknown';
                $details[$buyerId] = [
                    'ping' => $buyer,
                    'post' => null,
                    'final_status' => $buyer['status'] ?? 'unknown',
                ];
            }
        }

        if ($this->post_buyers) {
            foreach ($this->post_buyers as $buyer) {
                $buyerId = $buyer['buyer_id'] ?? $buyer['buyer'] ?? 'unknown';
                if (isset($details[$buyerId])) {
                    $details[$buyerId]['post'] = $buyer;
                    $details[$buyerId]['final_status'] = $buyer['status'] ?? 'unknown';
                } else {
                    $details[$buyerId] = [
                        'ping' => null,
                        'post' => $buyer,
                        'final_status' => $buyer['status'] ?? 'unknown',
                    ];
                }
            }
        }

        return $details;
    }

    /**
     * Get comprehensive performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'timing' => [
                'ping_time' => $this->ping_time,
                'post_time' => $this->post_time,
                'total_time' => $this->total_processing_time,
            ],
            'buyers' => [
                'total' => $this->total_buyers,
                'accepted' => $this->accepted_buyers,
                'rejected' => $this->rejected_buyers,
                'conversion_rate' => $this->conversion_rate,
            ],
            'financial' => [
                'revenue' => $this->post_revenue,
                'highest_bid' => $this->highest_bid,
                'revenue_per_buyer' => $this->revenue_per_buyer,
            ],
            'efficiency' => [
                'efficiency_score' => $this->efficiency_score,
                'status_summary' => $this->status_summary,
            ],
        ];
    }
}
