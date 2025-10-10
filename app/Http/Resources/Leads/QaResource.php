<?php

namespace App\Http\Resources\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $qa_status = json_decode($this->qa_status);
        $qa_td_status = json_decode($this->qa_td_status);

        return [
            'vendors_td' => $this->vendors_td,
            'buyer_id' => $this->buyer_id,
            'buyers' => $this->buyers,
            'phone_id' => $this->phone_id,
            'durations' => __toMinutes(intval($this->durations)),
            'o_durations' => intval($this->durations),
            'created_at' => $this->created_at,
            'offers' => $this->offers,
            'pub_list_id' => $this->pub_list_id,
            'date_sale' => $this->date_sale,
            'traffic_source_id' => $this->traffic_source_id,
            'ad_quality_error' => $qa_status->ad_quality_error ?? false,
            'not_interested' => $qa_status->not_interested ?? false,
            'not_qualified' => $qa_status->not_qualified ?? false,
            'call_dropped' => $qa_status->call_dropped ?? false,
            'ivr' => $qa_status->ivr ?? false,
            'hold_durations' => __toMinutes(intval($qa_td_status->hold_duration)),
            'o_hold_durations' => intval($qa_td_status->hold_duration),
            'status_td' => $qa_td_status->status_td,
            'reached_agent' => $qa_status->reached_agent ?? true,
            'caller_hung_up' => $qa_td_status->status_td == 'caller-hung-up' ? 1 : 0,
        ];
    }
}
