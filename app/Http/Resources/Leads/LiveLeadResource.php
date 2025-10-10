<?php

namespace App\Http\Resources\Leads;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveLeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'yieldpro_lead_id' => $this->yp_lead_id,
            'type' => $this->type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'zip_code' => $this->zip_code,
            'universal_lead_id' => $this->jornaya_lead_id,
            'trusted_form' => $this->jornaya_trusted_form,
            'sub_id' => $this->sub_name,
            'pub_list_id' => $this->publisher_id,
            'state' => $this->state,
            'data' => $this->data,
            'cpl' => $this->cpl,
            'campaign_name_id' => $this->campaign_name_id,
            'vendors_yp' => $this->publisher_name,
            'phone' => $this->phone,
            'calls' => $this->latestCall?->calls,
            'status' => $this->status,
            'sub_id5' => $this->sub_id5,
            'created_at' => Carbon::create($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
