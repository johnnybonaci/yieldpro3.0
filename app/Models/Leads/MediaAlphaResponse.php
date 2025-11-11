<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediaAlphaResponse extends Model
{
    use HasFactory;
    use FiltersTrait;

    protected $fillable = [
        'phone_id',
        'placement_id',
        'leadid_id',
        'ping_id',
        'ping_buyers',
        'ping_time',
        'ping_status',
        'ping_error',
        'ping_sent_at',
        'ping_raw_response',
        'ping_request_data',
        'post_buyers',
        'post_status',
        'post_revenue',
        'post_time',
        'post_error',
        'post_sent_at',
        'post_raw_response',
        'post_request_data',
        'total_buyers',
        'accepted_buyers',
        'rejected_buyers',
        'highest_bid',
        'winning_buyer',
        'status',
        'date_history',
    ];

    protected $casts = [
        'ping_buyers' => 'array',
        'ping_time' => 'decimal:3',
        'ping_sent_at' => 'datetime',
        'ping_raw_response' => 'array',
        'ping_request_data' => 'array',
        'post_buyers' => 'array',
        'post_revenue' => 'decimal:2',
        'post_time' => 'decimal:3',
        'post_sent_at' => 'datetime',
        'post_raw_response' => 'array',
        'post_request_data' => 'array',
        'highest_bid' => 'decimal:2',
    ];

    /**
     * Process ping response and save to database.
     * @param mixed|null $requestInfo
     */
    public function processPingResponse(array $response, bool $isError = false, $requestInfo = []): self
    {
        if ($isError) {
            $this->update([
                'ping_status' => 'error',
                'ping_error' => $response['error'] ?? 'Unknown error',
                'ping_sent_at' => now(),
                'ping_raw_response' => $response,
                'ping_request_data' => $requestInfo,
                'ping_time' => $response['response_time_ms'] ?? null,
                'date_history' => now()->format('Y-m-d'),
            ]);
        } else {
            $buyers = $response['buyers'] ?? [];
            $highestBid = 0;
            $totalBuyers = count($buyers);
            $acceptedBuyers = 0;

            foreach ($buyers as $buyer) {
                $buyerBid = isset($buyer['bid']) ? floatval($buyer['bid']) : 0;

                if ($buyerBid > 0) {
                    ++$acceptedBuyers;
                }

                if ($buyerBid > $highestBid) {
                    $highestBid = $buyerBid;
                }
            }

            $this->update([
                'ping_id' => $response['ping_id'] ?? null,
                'ping_buyers' => $buyers,
                'ping_time' => $response['time'] ?? $response['response_time_ms'] ?? null,
                'ping_status' => 'success',
                'ping_sent_at' => now(),
                'ping_raw_response' => $response,
                'ping_request_data' => $requestInfo,
                'total_buyers' => $totalBuyers,
                'accepted_buyers' => $acceptedBuyers,
                'rejected_buyers' => $totalBuyers - $acceptedBuyers,
                'highest_bid' => $highestBid > 0 ? $highestBid : null,
                'date_history' => now()->format('Y-m-d'),
            ]);
        }

        return $this;
    }

    /**
     * Process post response and save to database.
     * @param mixed|null $requestInfo
     */
    public function processPostResponse(array $response, bool $isError = false, $requestInfo = []): self
    {
        if ($isError) {
            $this->handleErrorResponse($response, $requestInfo);
        } else {
            $this->handleSuccessResponse($response, $requestInfo);
        }

        return $this;
    }

    /**
     * Handle error response.
     * @param mixed $requestInfo
     */
    private function handleErrorResponse(array $response, $requestInfo): void
    {
        $this->update([
            'post_status' => 'failed',
            'post_error' => $response['error'] ?? 'Unknown error',
            'post_sent_at' => now(),
            'post_raw_response' => $response,
            'post_request_data' => $requestInfo,
            'post_time' => $response['response_time_ms'] ?? $response['time'] ?? null,
            'status' => 'failed',
        ]);
    }

    /**
     * Handle success response.
     * @param mixed $requestInfo
     */
    private function handleSuccessResponse(array $response, $requestInfo): void
    {
        $buyers = $response['buyers'] ?? [];
        $revenue = $response['rev'] ?? $response['revenue'] ?? 0;

        $winningBuyerData = $this->determineWinningBuyer($buyers, $requestInfo);
        $winningBuyer = $winningBuyerData['buyer'];
        $finalRevenue = $winningBuyerData['revenue'] ?? $revenue;

        $this->update([
            'post_buyers' => $buyers,
            'post_status' => $response['status'] ?? 'succeeded',
            'post_revenue' => $finalRevenue,
            'post_time' => $response['time'] ?? $response['response_time_ms'] ?? null,
            'post_sent_at' => now(),
            'post_raw_response' => $response,
            'post_request_data' => $requestInfo,
            'winning_buyer' => $winningBuyer,
            'status' => $finalRevenue > 0 ? 'completed' : 'completed_no_revenue',
        ]);
    }

    /**
     * Determine winning buyer from buyers array or request info.
     * @param mixed $requestInfo
     */
    private function determineWinningBuyer(array $buyers, $requestInfo): array
    {
        if (empty($buyers)) {
            return $this->getWinningBuyerFromRequest($requestInfo);
        }

        $highestBidBuyer = $this->findHighestBidBuyer($buyers);

        if (!$highestBidBuyer) {
            return $this->getWinningBuyerFromRequest($requestInfo);
        }

        return [
            'buyer' => $highestBidBuyer['buyer'] ?? $highestBidBuyer['buyer_id'] ?? $highestBidBuyer['name'] ?? null,
            'revenue' => isset($highestBidBuyer['rev']) && $highestBidBuyer['rev'] > 0 ? $highestBidBuyer['rev'] : null,
        ];
    }

    /**
     * Find buyer with highest bid.
     */
    private function findHighestBidBuyer(array $buyers): ?array
    {
        $highestBidBuyer = null;
        $highestBid = 0;

        foreach ($buyers as $buyer) {
            $currentBid = $buyer['bid'] ?? 0;
            if ($currentBid > $highestBid) {
                $highestBid = $currentBid;
                $highestBidBuyer = $buyer;
            }
        }

        return $highestBidBuyer;
    }

    /**
     * Get winning buyer from request info fallback.
     * @param mixed $requestInfo
     */
    private function getWinningBuyerFromRequest($requestInfo): array
    {
        $buyer = null;

        if (is_array($requestInfo) && isset($requestInfo['selected_buyer'])) {
            $buyer = $requestInfo['selected_buyer']['buyer'] ?? null;
        }

        return ['buyer' => $buyer, 'revenue' => null];
    }

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

    // Nuevos scopes para análisis más detallado
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

    // Atributos calculados mejorados
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

    // Relaciones
    public function config()
    {
        return $this->belongsTo(MediaAlphaConfig::class, 'placement_id', 'placement_id');
    }

    // Métodos para análisis de datos
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
