<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Pub;
use App\Models\LeadsClone;
use App\Models\Leads\Provider;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\Leads\LeadService;
use App\Repositories\Leads\LeadApiRepository;

class UploadLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess Leads Clone';

    /**
     * Summary of handle.
     */
    public function handle(LeadService $lead_service): void
    {
        $this->info('Reprocess Leads:');

        $lead = LeadsClone::select(['leads_clone.phone', 'leads_clone.first_name', 'leads_clone.last_name', 'leads_clone.email', 'leads_clone.type', 'leads_clone.zip_code', 'leads_clone.state', 'leads_clone.ip', 'leads_clone.cpl', 'leads_clone.data', 'leads_clone.yp_lead_id', 'leads_clone.campaign_name_id', 'leads_clone.universal_lead_id', 'leads_clone.trusted_form', 'leads_clone.sub_id', 'leads_clone.pub_id', 'leads_clone.date_history', 'leads_clone.created_at', 'leads_clone.updated_at'])
            ->where('pub_id', 103)
            ->get();
        $this->info($lead->count() . ' Leads imported');
        $lead_api_repository = new LeadApiRepository();
        $providers = $lead_api_repository->getActiveAll(new Provider());
        $lead->map(function ($item) use ($lead_api_repository, $providers) {
            $insert = $this->resource($item->toArray());
            $pub = $lead_api_repository->find($insert['pub_id'], new Pub());
            // Logic to Send Provider
            $providers->each(function ($provider) use ($insert, $pub, $lead_api_repository) {
                if ($lead_api_repository->checkPostingLead($pub, $provider)) {
                    $lead_api_repository->process($insert, __toJob($provider));
                }
            });

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
