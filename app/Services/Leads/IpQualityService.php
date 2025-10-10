<?php

namespace App\Services\Leads;

use Illuminate\Support\Facades\Http;

class IpQualityService
{
    public const BASE_URL = 'https://www.ipqualityscore.com/api/json/ip';

    /**
     * Posting Lead to PhoneRoom TruAlliant.
     */
    public function index(string $ip): bool
    {
        $key = env('IP_QUALITY_SCORE_KEY');

        $result = Http::get(self::BASE_URL . "/{$key}/$ip?strictness=3&allow_public_access_points=true&fast=true&lighter_penalties=true")->json();
        if ($result['success'] && $result['fraud_score'] <= 35 && !$result['proxy'] && !$result['vpn'] && !$result['tor'] && !$result['recent_abuse'] && !$result['active_vpn'] && !$result['active_tor']) {
            return true;
        }

        return false;
    }
}
