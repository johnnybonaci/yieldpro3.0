<?php

namespace App\Services\Leads;

use Illuminate\Support\Facades\Log;
use App\Models\Leads\MediaAlphaResponse;

class MediaAlphaWebhookService
{
    /**
     * Process ping response and save to database.
     * @param mixed|null $requestInfo
     */
    public static function storePingResponse(
        string $phone,
        string $placementId,
        array $response,
        bool $isError = false,
        ?string $leadidId = null,
        $requestInfo = null,
    ): MediaAlphaResponse {
        $cleanPhone = self::cleanPhone($phone);

        $mediaResponse = MediaAlphaResponse::firstOrCreate(
            ['phone_id' => $cleanPhone],
            [
                'placement_id' => $placementId,
                'leadid_id' => $leadidId,
                'status' => 'processing',
            ]
        );

        $mediaResponse->processPingResponse($response, $isError, $requestInfo);

        return $mediaResponse;
    }

    /**
     * Process post response and update in database.
     * @param mixed|null $requestInfo
     */
    public static function storePostResponse(
        string $phone,
        array $response,
        bool $isError = false,
        $requestInfo = null,
    ): ?MediaAlphaResponse {
        $cleanPhone = self::cleanPhone($phone);

        // Find the existing record
        $mediaResponse = MediaAlphaResponse::find($cleanPhone);

        if (!$mediaResponse) {
            Log::warning('MediaAlpha Post Response - No ping record found', [
                'phone_id' => $cleanPhone,
                'response' => $response,
            ]);

            return null;
        }

        $mediaResponse->processPostResponse($response, $isError, $requestInfo);

        return $mediaResponse;
    }

    /**
     * Process complete response (ping + post in a single call).
     */
    public static function storeCompleteResponse(
        string $phone,
        string $placementId,
        array $pingResponse,
        array $postResponse,
        bool $pingError = false,
        bool $postError = false,
        ?string $leadidId = null,
    ): MediaAlphaResponse {
        $cleanPhone = self::cleanPhone($phone);

        // Create the record
        $mediaResponse = MediaAlphaResponse::firstOrCreate(
            ['phone_id' => $cleanPhone],
            [
                'placement_id' => $placementId,
                'leadid_id' => $leadidId,
                'status' => 'processing',
            ]
        );

        $mediaResponse->processPingResponse($pingResponse, $pingError);

        $mediaResponse->processPostResponse($postResponse, $postError);

        return $mediaResponse;
    }

    /**
     * Clean phone number to use as key.
     */
    private static function cleanPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // If it has 11 digits and starts with 1, remove the 1
        if (strlen($cleaned) === 11 && $cleaned[0] === '1') {
            $cleaned = substr($cleaned, 1);
        }

        // Format as (XXX) XXX-XXXX if it has 10 digits
        if (strlen($cleaned) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6, 4)
            );
        }

        return $phone;
    }

    /**
     * Get quick stats for dashboard.
     */
    public static function getQuickStats(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        return [
            'today' => [
                'total' => MediaAlphaResponse::where('created_at', '>=', $today)->count(),
                'successful' => MediaAlphaResponse::where('created_at', '>=', $today)->successful()->count(),
                'revenue' => MediaAlphaResponse::where('created_at', '>=', $today)->sum('post_revenue') ?? 0,
                'avg_ping_time' => MediaAlphaResponse::where('created_at', '>=', $today)->avg('ping_time') ?? 0,
                'avg_post_time' => MediaAlphaResponse::where('created_at', '>=', $today)->avg('post_time') ?? 0,
            ],
            'yesterday' => [
                'total' => MediaAlphaResponse::whereBetween('created_at', [$yesterday, $today])->count(),
                'successful' => MediaAlphaResponse::whereBetween('created_at', [$yesterday, $today])->successful()->count(),
                'revenue' => MediaAlphaResponse::whereBetween('created_at', [$yesterday, $today])->sum('post_revenue') ?? 0,
                'avg_ping_time' => MediaAlphaResponse::whereBetween('created_at', [$yesterday, $today])->avg('ping_time') ?? 0,
                'avg_post_time' => MediaAlphaResponse::whereBetween('created_at', [$yesterday, $today])->avg('post_time') ?? 0,
            ],
            'last_7_days' => [
                'total' => MediaAlphaResponse::where('created_at', '>=', now()->subDays(7))->count(),
                'successful' => MediaAlphaResponse::where('created_at', '>=', now()->subDays(7))->successful()->count(),
                'revenue' => MediaAlphaResponse::where('created_at', '>=', now()->subDays(7))->sum('post_revenue') ?? 0,
                'avg_ping_time' => MediaAlphaResponse::where('created_at', '>=', now()->subDays(7))->avg('ping_time') ?? 0,
                'avg_post_time' => MediaAlphaResponse::where('created_at', '>=', now()->subDays(7))->avg('post_time') ?? 0,
            ],
        ];
    }

    /**
     * Get top buyers by revenue.
     */
    public static function getTopBuyers(int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = MediaAlphaResponse::whereNotNull('winning_buyer')
            ->whereNotNull('post_revenue')
            ->where('post_revenue', '>', 0);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->selectRaw('winning_buyer, COUNT(*) as lead_count, SUM(post_revenue) as total_revenue, AVG(post_revenue) as avg_revenue')
            ->groupBy('winning_buyer')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'buyer' => $item->winning_buyer,
                    'lead_count' => (int) $item->lead_count,
                    'total_revenue' => round($item->total_revenue, 2),
                    'avg_revenue' => round($item->avg_revenue, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get performance by placement_id.
     */
    public static function getPlacementPerformance(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = MediaAlphaResponse::with('config:placement_id,name');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->selectRaw('
                placement_id,
                COUNT(*) as total_leads,
                SUM(CASE WHEN ping_status = "success" AND post_status = "succeeded" THEN 1 ELSE 0 END) as successful_leads,
                SUM(CASE WHEN ping_status = "error" OR post_status = "failed" THEN 1 ELSE 0 END) as failed_leads,
                SUM(post_revenue) as total_revenue,
                AVG(post_revenue) as avg_revenue,
                SUM(total_buyers) as total_buyers_contacted,
                SUM(accepted_buyers) as total_buyers_accepted,
                AVG(ping_time) as avg_ping_time,
                AVG(post_time) as avg_post_time
            ')
            ->groupBy('placement_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($item) {
                $successRate = $item->total_leads > 0 ? round(($item->successful_leads / $item->total_leads) * 100, 2) : 0;
                $buyerAcceptanceRate = $item->total_buyers_contacted > 0 ? round(($item->total_buyers_accepted / $item->total_buyers_contacted) * 100, 2) : 0;

                return [
                    'placement_id' => $item->placement_id,
                    'config_name' => $item->config->name ?? 'No configuration',
                    'total_leads' => (int) $item->total_leads,
                    'successful_leads' => (int) $item->successful_leads,
                    'failed_leads' => (int) $item->failed_leads,
                    'success_rate' => $successRate,
                    'total_revenue' => round($item->total_revenue ?? 0, 2),
                    'avg_revenue' => round($item->avg_revenue ?? 0, 2),
                    'total_buyers_contacted' => (int) $item->total_buyers_contacted,
                    'total_buyers_accepted' => (int) $item->total_buyers_accepted,
                    'buyer_acceptance_rate' => $buyerAcceptanceRate,
                    'avg_ping_time' => round($item->avg_ping_time ?? 0, 3),
                    'avg_post_time' => round($item->avg_post_time ?? 0, 3),
                    'avg_total_time' => round(($item->avg_ping_time ?? 0) + ($item->avg_post_time ?? 0), 3),
                ];
            })
            ->toArray();
    }

    /**
     * Validate request data before storing.
     * @param mixed $requestInfo
     */
    private static function validateRequestData($requestInfo): array
    {
        if (is_string($requestInfo)) {
            // Si es un string (cURL command), mantener compatibilidad
            return ['curl_command' => $requestInfo];
        }

        if (is_array($requestInfo)) {
            return $requestInfo;
        }

        return ['raw_data' => $requestInfo];
    }
}
