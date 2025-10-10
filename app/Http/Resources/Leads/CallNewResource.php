<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallNewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $insurance = $this->determineInsurance($this->ai_insurance_status, $this->offer_id);

        return [
            'id' => $this->id,
            'url' => $this->url,
            'transcript' => $this->transcript,
            'billable' => $this->ai_sale_status,
            'status_t' => $this->ai_status,
            'multiple' => $this->ai_analysis ? (object) $this->ai_analysis : null,
            'did_number_id' => $this->did_number_id,
            'cpl' => $this->cpl,
            'vendors_td' => $this->traffic_source_name,
            'status' => $this->status,
            'buyer_id' => $this->buyer_id,
            'buyers' => $this->buyer_name,
            'revenue' => $this->revenue,
            'phone_id' => $this->phone_id,
            'terminating_phone' => $this->terminating_phone,
            'durations' => __toMinutes(intval($this->durations)),
            'created_at' => $this->created_at,
            'offers' => $this->offer_name,
            'offer_id' => $this->offer_id,
            'pub_list_id' => $this->lead_publisher_id,
            'date_sale' => $this->td_created_at->format('Y-m-d H:i:s'),
            'calls' => $this->calls,
            'converted' => $this->converted,
            'traffic_source_id' => $this->traffic_source_id,
            'insurance' => $insurance,
            'insurance_value' => $this->ai_insurance_status,
            'insurance_name' => json_decode($this->multiple, true)['existing_insurance_name'] ?? '-',
            'call_ending_sooner_reason' => json_decode($this->multiple, true)['call_ending_sooner_reason']
                ?? json_decode($this->multiple, true)['call_ending_sooner_reasons'][0]['category']
                ?? null,
            'state' => $this->state,
            'sub_id5' => $this->lead_sub_id5,
        ];
    }

    private function determineInsurance(mixed $insurance, mixed $offerId): string
    {
        if ($offerId == 20012) {
            return 'N/A';
        }

        return match ($insurance) {
            1 => 'Yes',
            2 => 'No',
            default => 'N/A',
        };
    }
}
