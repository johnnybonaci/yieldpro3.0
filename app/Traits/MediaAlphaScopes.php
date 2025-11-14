<?php

namespace App\Traits;

/**
 * MediaAlpha Query Scopes Trait
 *
 * Extracts all query scopes from MediaAlphaResponse model
 * to reduce method count and improve SonarCube compliance.
 */
trait MediaAlphaScopes
{
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPlacementId($query, string $placementId)
    {
        return $query->where('placement_id', $placementId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeWithRevenue($query)
    {
        return $query->whereNotNull('post_revenue')->where('post_revenue', '>', 0);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('ping_status', 'success')
            ->where('post_status', 'succeeded');
    }

    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('ping_status', 'error')
                ->orWhere('post_status', 'failed');
        });
    }

    public function scopeWithValidBuyers($query)
    {
        return $query->where('total_buyers', '>', 0);
    }

    public function scopeWithAcceptedBuyers($query)
    {
        return $query->where('accepted_buyers', '>', 0);
    }

    public function scopeByRevenueRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('post_revenue', '>=', $min);
        }
        if ($max !== null) {
            $query->where('post_revenue', '<=', $max);
        }

        return $query;
    }
}
