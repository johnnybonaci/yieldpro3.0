<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CpaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_revenue' => $this['total_revenue'] ?? 0,
            'total_cost' => $this['total_cost'] ?? 0,
            'total_calls' => $this['total_calls'] ?? 0,
            'total_billables' => $this['total_billables'] ?? 0,
            'total_sales' => $this['total_sales'] ?? 0,
            'total_durations' => __toMinutes($this['durations']) ?? 0,
            'total_cpa' => $this['total_cpa'] ?? 0,
            'total_ucr' => $this['total_ucr'] ?? 0,
            'total_ucr_1' => $this['total_ucr_1'] ?? 0,
            'total_unique' => $this['total_unique'] ?? 0,
            'total_cpc' => $this['total_cpc'] ?? 0,
            'buyer_name' => $this['buyer_name'],
            '_children' => $this['_children'] ?? [],
            'state' => '',
        ];
    }
}
