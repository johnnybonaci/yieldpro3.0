<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RpcResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $revenue = $this['total_revs'] ?? 0;
        $spend = $this['total_spend'] ?? 0;

        return [
            'total_revenue' => $this['total_revenue'] ?? 0,
            'total_profit' => $revenue - $spend,
            'total_revs' => $this['total_revs'] ?? 0,
            'total_calls' => $this['total_calls'] ?? 0,
            'total_unique' => $this['total_unique'] ?? 0,
            'total_durations' => __toMinutes($this['durations']) ?? 0,
            'buyer_name' => $this['buyer_name'],
            'state' => '',
            'total_billables' => $this['total_billables'] ?? 0,
            'total_rpc' => $this['total_rpc'] ?? 0,
            '_children' => $this['_children'] ?? [],
        ];
    }
}
