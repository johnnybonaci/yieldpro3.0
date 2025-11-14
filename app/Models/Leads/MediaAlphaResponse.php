<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use App\Traits\MediaAlphaScopes;
use App\Traits\MediaAlphaMetrics;
use App\Traits\MediaAlphaAnalytics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * MediaAlphaResponse Model - Refactored for SonarCube Compliance
 *
 * Reduced from 25 methods to 9 methods by extracting:
 * - 9 query scopes → MediaAlphaScopes trait
 * - 6 computed attributes → MediaAlphaMetrics trait
 * - 2 analytics methods → MediaAlphaAnalytics trait
 *
 * Now complies with SonarCube's 20-method limit.
 */
class MediaAlphaResponse extends Model
{
    use HasFactory;
    use FiltersTrait;
    use MediaAlphaScopes;      // Query scopes (9 methods)
    use MediaAlphaMetrics;     // Computed attributes (6 methods)
    use MediaAlphaAnalytics;   // Analytics methods (2 methods)

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

    // Relaciones
    public function config()
    {
        return $this->belongsTo(MediaAlphaConfig::class, 'placement_id', 'placement_id');
    }
}
