<?php

namespace App\Console\Commands;

use App\Models\Leads\Call;
use Illuminate\Support\Arr;
use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Models\Leads\Convertion;
use Illuminate\Support\Facades\Date;

class MigrateCallsCommand extends Command
{
    protected $signature = 'migrate:calls {from}';

    protected $description = 'Migrate leads to live_leads table';

    public function handle(): void
    {
        $from = Date::parse($this->argument('from'));

        $convertionQuery = Convertion::query()->orderByDesc('id')->with([
            'record',
            'buyer',
            'lead',
            'lead.pubs',
            'lead.pubs.pub_lists',
            'offer',
            'trafficSource',
        ])->where('date_history', '=', $from->format('Y-m-d'));

        $bar = $this->output->createProgressBar($convertionQuery->count());

        $bar->start();

        $buffer = [];

        /** @var Convertion $convertion */
        foreach ($convertionQuery->lazy(1000) as $convertion) {
            /** @var Recording $recording */
            $recording = $convertion->record->first();

            $data = [
                'phone_id' => $convertion->phone_id,
                'convertion_id' => $convertion->id,
                'outside' => $convertion->outside,
                'answered' => $convertion->answered,
                'status' => $convertion->status,
                'revenue' => $convertion->revenue,
                'cpl' => $convertion->cpl,
                'durations' => $convertion->durations,
                'calls' => $convertion->calls,
                'converted' => $convertion->converted,
                'terminating_phone' => $convertion->terminating_phone,
                'did_number_id' => $convertion->did_number_id,
                'buyer_id' => $convertion->buyer_id,
                'buyer_name' => $convertion->buyer?->name,
                'offer_id' => $convertion->offer_id,
                'offer_name' => $convertion->offer?->name,
                'traffic_source_id' => $convertion->traffic_source_id,
                'traffic_source_name' => $convertion->trafficSource?->name,

                'lead_first_name' => $convertion->lead?->first_name,
                'lead_last_name' => $convertion->lead?->last_name,
                'lead_email' => $convertion->lead?->email,
                'lead_type' => $convertion->lead?->type,
                'lead_sub_id' => $convertion->lead?->sub_id,
                'lead_pub_id' => $convertion->lead?->pub_id,
                'lead_publisher_id' => $convertion->lead?->pubs?->pub_lists?->id,
                'lead_campaign_name_id' => $convertion->lead?->campaign_name_id,
                'lead_created_at_date' => $convertion->lead?->date_history,
                'lead_updated_at_date' => $convertion->lead?->updated_at?->format('Y-m-d'),
                'lead_cpl' => $convertion->lead?->cpl !== null ? $convertion->lead?->cpl : null,
                'lead_sub_id5' => $convertion->lead?->sub_id5,

                'td_created_at_date' => $convertion->date_history,
                'td_created_at' => $convertion->created_at,
                'td_updated_at' => $convertion->updated_at,
                'recording_id' => $recording?->id,
                'url' => $recording?->url,
                'record' => $recording?->record,
                'transcript' => $recording?->transcript,
                'ai_sale_status' => $recording?->billable,
                'ai_insurance_status' => $recording?->insurance,
                'ai_status' => $recording?->status,
                'state' => $convertion->lead?->state,
                'ai_analysis' => $recording?->multiple,
                'ai_qa_analysis' => $recording?->qa_status,
                'td_qa_status' => $recording?->qa_td_status,
            ];

            $buffer[] = $data;
            if (count($buffer) === 10) {
                Call::query()->upsert($buffer, ['convertion_id'], Arr::except(array_keys($data), ['convertion_id', 'phone_id']));

                $buffer = [];

                $bar->advance(10);
            }
        }

        if (count($buffer) > 0) {
            Call::query()->upsert($buffer, ['convertion_id'], Arr::except(array_keys($buffer[0]), ['convertion_id', 'phone_id']));
        }

        $bar->advance(count($buffer));
    }
}
