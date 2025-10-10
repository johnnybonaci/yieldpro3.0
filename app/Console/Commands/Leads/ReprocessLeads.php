<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\Leads\LeadService;

class ReprocessLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reprocess:leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess Leads';

    /**
     * Summary of handle.
     */
    public function handle(LeadService $lead_service): void
    {
        $this->info('Reprocess Leads:');

        $lead = Lead::select(['leads.phone', 'leads.first_name', 'leads.last_name', 'leads.email', 'leads.type', 'leads.zip_code', 'leads.state', 'leads.ip', 'leads.cpl', 'leads.data', 'leads.yp_lead_id', 'leads.campaign_name_id', 'leads.universal_lead_id', 'leads.trusted_form', 'leads.sub_id', 'leads.pub_id', 'leads.date_history', 'leads.created_at', 'leads.updated_at'])
            ->where('sub_id3', 'pro-RME-ACA-0826-DB007')
            ->take(1)
            ->get();
        $this->info($lead->count() . ' Leads imported');
        $lead->map(function ($item) use ($lead_service) {
            $insert = $this->resource($item->toArray());
            if (!in_array($insert['email'], ['aca_goquote_home4@api.com', 'aca_benefit_home4@api.com'])) {
                $lead_service->dispatch($insert, true);
            }

            return $insert;
        });
    }

    public function resource(array $data): Collection
    {
        $data = [
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'type' => $data['type'],
            'zip_code' => $data['zip_code'],
            'state' => $data['state'],
            'ip' => $data['ip'],
            'cpl' => $data['cpl'],
            'data' => $data['data'],
            'yp_lead_id' => $data['yp_lead_id'],
            'campaign_name_id' => $data['campaign_name_id'],
            'universal_lead_id' => $data['universal_lead_id'],
            'trusted_form' => $data['trusted_form'],
            'sub_id' => $data['sub_id'],
            'pub_id' => $data['pub_id'],
            'date_history' => $data['date_history'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at'],
            'white_list' => true,
        ];
        $response = new Collection($data);

        return $response;
    }
}
