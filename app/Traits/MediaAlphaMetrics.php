<?php

namespace App\Traits;

/**
 * MediaAlpha Metrics Trait
 *
 * Extracts computed attributes from MediaAlphaResponse model
 * to reduce method count and improve SonarCube compliance.
 */
trait MediaAlphaMetrics
{
    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_id);
        if (strlen($phone) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }

        return $this->phone_id;
    }

    public function getTotalProcessingTimeAttribute(): ?float
    {
        if ($this->ping_time && $this->post_time) {
            return round($this->ping_time + $this->post_time, 3);
        }

        return null;
    }

    public function getConversionRateAttribute(): ?float
    {
        if ($this->total_buyers > 0) {
            return round(($this->accepted_buyers / $this->total_buyers) * 100, 2);
        }

        return null;
    }

    public function getRevenuePerBuyerAttribute(): ?float
    {
        if ($this->accepted_buyers > 0 && $this->post_revenue > 0) {
            return round($this->post_revenue / $this->accepted_buyers, 2);
        }

        return null;
    }

    public function getEfficiencyScoreAttribute(): ?float
    {
        // Score basado en revenue, tiempo de respuesta y tasa de conversión
        if (!$this->post_revenue || !$this->total_processing_time || !$this->conversion_rate) {
            return null;
        }

        // Fórmula: (Revenue * Conversion Rate) / Processing Time
        return round(($this->post_revenue * $this->conversion_rate) / $this->total_processing_time, 2);
    }

    public function getStatusSummaryAttribute(): array
    {
        return [
            'overall_status' => $this->status,
            'ping_successful' => $this->ping_status === 'success',
            'post_successful' => $this->post_status === 'succeeded',
            'has_revenue' => $this->post_revenue > 0,
            'has_buyers' => $this->total_buyers > 0,
            'has_accepted_buyers' => $this->accepted_buyers > 0,
        ];
    }
}
