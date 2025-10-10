<?php

namespace App\Console\Commands;

use App\Models\Leads\Lead;
use Illuminate\Support\Arr;
use App\Models\Leads\LiveLead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class MigrateLeadsCommand extends Command
{
    protected $signature = 'migrate:leads {from} {today?} {active?}';

    protected $description = 'Migrate leads to live_leads table';

    public function handle(): void
    {
        $from = Date::parse($this->argument('from'));

        $today = $this->argument('today') ?? 1;

        $active = $this->argument('active') ?? 0;

        $leadQuery = Lead::query()->orderByDesc('created_at')->orderByDesc('phone')->with([
            'jornaya',
            'subs',
            'pubs',
            'pubs.pub_lists',
            'pubs.offers',
            'originalPub',
            'originalPub.pub_lists',
            'originalPub.offers',
        ])
            ->when($today, function ($query) use ($from) {
                $query->where('date_history', '=', $from->format('Y-m-d'));
            })
            ->when($active, function ($query) use ($from) {
                $query->whereIn('phone', function ($query) use ($from) {
                    $query->select('convertions.phone_id')
                        ->from('convertions')
                        ->join('leads', function ($join) use ($from) {
                            $join->on('leads.phone', '=', 'convertions.phone_id')
                                ->where('convertions.date_history', '=', $from->format('Y-m-d'));
                        })
                        ->where('leads.date_history', '!=', $from->format('Y-m-d'));
                });
            });

        $bar = $this->output->createProgressBar($leadQuery->count());

        $bar->start();

        $buffer = [];
        /** @var Lead $lead */
        foreach ($leadQuery->lazy(100000) as $lead) {
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

            $buffer[] = $data;
            if (count($buffer) === 10) {
                LiveLead::query()->upsert($buffer, ['phone'], Arr::except(array_keys($data), ['phone']));

                $buffer = [];

                $bar->advance(10);
            }
        }

        if (count($buffer) > 0) {
            LiveLead::query()->upsert($buffer, ['phone'], Arr::except(array_keys($buffer[0]), ['phone']));
        }

        $bar->advance(count($buffer));
    }
}
