<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Lead;
use App\ValueObjects\Period;
use Illuminate\Console\Command;
use App\Services\Leads\LeadService;

class ImportLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:leads {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Leads from any Sources';

    /**
     * Summary of handle.
     */
    public function handle(LeadService $lead_service): void
    {
        $date = $this->argument('date') ? Period::today($this->argument('date')) : Period::today();
        $this->info("Fetch Leads Date: {$date->from()->toDateString()}");
        $this->info($lead_service->import(new Lead(), env('TRACKDRIVE_PROVIDER_ID'), $date) . ' Leads imported');
    }
}
