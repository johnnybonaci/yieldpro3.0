<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\BotLeads;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Repositories\Leads\LeadApiRepository;

class ReprocessBotLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reprocess:bot:leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess BotLeads';

    /**
     * Summary of handle.
     */

    /**
     * Summary of handle.
     */
    public function handle(LeadApiRepository $lead_api_repository): void
    {
        $this->info('Reprocess Leads:');
        $created_at = now()->subSeconds(90)->format('Y-m-d H:i:s');
        $lead = BotLeads::select('bot_leads.*')->leftJoin('leads', 'leads.phone', '=', 'bot_leads.phone')->whereNull('leads.phone')->whereNotNull('bot_leads.pub_id')->where('bot_leads.created_at', '<=', $created_at)->whereNotIn('bot_leads.pub_id', ['172', '173', '181'])->where('bot_leads.rejected', false)->get();
        $this->info($lead->count() . ' Leads imported');
        $lead->map(function ($item) use ($lead_api_repository) {
            $insert = $this->resource($item->toArray());
            $lead_api_repository->jobBot($insert);
        });
    }

    public function resource(array $data): Collection
    {
        $data = [
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'zip_code' => $data['zip_code'],
            'state' => $data['state'],
            'campaign_name_id' => $data['campaign_name_id'] ?? 'default',
            'type' => $data['type'],
            'pub_id' => $data['pub_id'],
        ];
        $response = new Collection($data);

        return $response;
    }
}
