<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageViewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = match ($this['type']) {
            '1' => 'ACA',
            '2' => 'MC',
            '3' => 'MC_IB',
            '4' => 'ACA_IB',
            default => $this['type'],
        };

        return [
            'date_history' => $this['date_history'],
            'type' => $type,
            'pub_id' => $this['pub_id'],
            'leads' => $this['leads'],
            'leads_dup' => $this['leads_dup'],
            'total_leads' => $this['total_leads'],
            'unique_leads' => $this['unique_leads'],
        ];
    }
}
