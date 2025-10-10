<?php

namespace App\Jobs\Leads;

use App\Models\Leads\Lead;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\Models\Leads\LiveLead;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateLiveLeadJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $leadPhone)
    {
    }

    public function handle(): void
    {
        $lead = Lead::query()->with([
            'jornaya',
            'subs',
            'pubs',
            'pubs.pub_lists',
            'pubs.offers',
            'originalPub',
            'originalPub.pub_lists',
            'originalPub.offers',
        ])->where('phone', $this->leadPhone)->first();

        $jornaya = $lead->jornaya->where('universal_lead_id', $lead->universal_lead_id)->first();

        $data = [
            'phone' => $lead->phone,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'zipcode' => $lead->zip_code,
            'state' => $lead->state,
            'type' => $lead->type,
            'cpl' => $lead->cpl,
            'campaign_name_id' => $lead->campaign_name_id,
            'jornaya_id' => $jornaya?->id,
            'jornaya_lead_id' => $lead->universal_lead_id,
            'jornaya_trusted_form' => $lead->trusted_form,
            'sub_id' => $lead->sub_id,
            'sub_name' => $lead->subs->sub_id,
            'pub_id' => $lead->pub_id,
            'pub_offer_id' => $lead?->pubs?->offers?->id,
            'pub_offer_name' => $lead?->pubs?->offers?->name,
            'publisher_id' => $lead?->pubs?->pub_lists?->id,
            'publisher_name' => $lead?->pubs?->pub_lists?->name,
            'original_type' => $lead->sub_id4,
            'original_campaign_name_id' => $lead->sub_id3,
            'original_pub_id' => $lead->sub_id2,
            'original_pub_offer_id' => $lead->originalPub?->offers?->id,
            'original_pub_offer_name' => $lead->originalPub?->offers?->name,
            'original_publisher_id' => $lead->originalPub?->pub_lists?->id,
            'original_publisher_name' => $lead->originalPub?->pub_lists?->name,
            'sub_id5' => $lead->sub_id5,
            'created_at' => $lead->created_at,
            'created_at_date' => $lead->date_history,
            'updated_at' => $lead->updated_at,
            'updated_at_date' => $lead->updated_at->format('Y-m-d'),
            'data' => is_null($lead->data) ? null : json_encode($lead->data),
        ];

        LiveLead::query()->upsert([$data], ['phone'], Arr::except(array_keys($data), ['phone']));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->leadPhone;
    }
}
