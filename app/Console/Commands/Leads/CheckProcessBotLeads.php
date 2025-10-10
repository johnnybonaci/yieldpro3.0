<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\BotLeads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Notifications\LeadsLimitExceeded;
use Illuminate\Support\Facades\Notification;

class CheckProcessBotLeads extends Command
{
    protected $signature = 'leads:check-process';

    protected $description = 'Check if any pub_id exceeds 50 leads and notify';

    public function handle()
    {
        $day = now()->subDays(3)->format('Y-m-d');
        $results = BotLeads::leftJoin('leads', 'leads.phone', '=', 'bot_leads.phone')
            ->select('bot_leads.pub_id', DB::raw('COUNT(bot_leads.phone) as total'))
            ->whereNull('leads.phone')
            ->whereNotNull('bot_leads.pub_id')
            ->where('bot_leads.rejected', 0)
            ->where('bot_leads.date_history', '>=', $day)
            ->groupBy('bot_leads.pub_id')
            ->having('total', '>', 50)
            ->get();

        if ($results->isEmpty()) {
            $this->info('No pub_id exceeds the limit of 50 leads.');

            return;
        }

        Notification::route('mail', ['johnny@massnexus.com', 'nico@massnexus.com'])
            ->notify(new LeadsLimitExceeded($results));

        $this->info('Notifications sent successfully.');
    }
}
