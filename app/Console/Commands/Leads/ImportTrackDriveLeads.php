<?php

namespace App\Console\Commands\Leads;

ini_set('memory_limit', '-1');

use App\ValueObjects\Period;
use Illuminate\Console\Command;
use App\Models\Leads\Convertion;
use App\Services\Leads\TrackDriveService;

class ImportTrackDriveLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:trackdrive:leads {provider} {date?} {--process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Leads from TrackDrive API and insert into table Leads & Convertions';

    /**
     * Summary of handle.
     */
    public function handle(TrackDriveService $track_drive_service): void
    {
        $provider = $this->argument('provider');
        $command = $this->option('process') ?? false;
        $date = $this->argument('date') ? Period::today($this->argument('date')) : Period::today();

        $this->info("Fetch Leads Date: {$date->from()->toDateString()}");
        $this->info('Fetch Calls & Convertions from TrackDrive API and insert into table call_logs & convertions');
        $this->info($track_drive_service->import(new Convertion(), $provider, $date, $command) . ' Leads imported');
    }
}
