<?php

namespace App\Http\Resources\Leads;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneRoomResource extends JsonResource
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
            'sub_id' => $this->sub_id,
            'pub_list_id' => $this->pub_list_id,
            'vendors_yp' => $this->vendors_yp,
            'phone' => $this->phone,
            'status' => $this->status === 1 ? 'sent' : 'rejected',
            'log' => $this->log == '' || $this->log == '[]' ? 'N/A' : $this->log,
            'request' => $this->request == '' ? 'N/A' : $this->request,
            'phone_room_lead_id' => $this->phone_room_lead_id,
            'created_at' => Carbon::create($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
