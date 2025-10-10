<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Buyer;
use App\Models\Leads\Offer;
use App\ValueObjects\Period;
use App\Models\Leads\DidNumber;
use Illuminate\Console\Command;
use App\Models\Leads\TrafficSource;
use App\Services\Leads\ImportService;

class ImportTrackDriveData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:trackdrive:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Data from TrackDrive API and insert into table';

    /**
     * Summary of handle.
     */
    public function handle(ImportService $import_service): void
    {
        // Import Traffic Sources
        $provider = env('TRACKDRIVE_PROVIDER_ID');

        $this->info('Fetch Data Traffic Sources from TrackDrive API and insert into table traffic_sources');
        $this->info($import_service->import(new TrafficSource(), $provider, Period::today()) . ' Traffic Sources imported');

        // Import Buyer
        $this->info('Fetch Data Buyer from TrackDrive API and insert into table buyers');
        $this->info($import_service->import(new Buyer(), $provider, Period::today()) . ' Buyer imported');

        // Import Offers
        $this->info('Fetch Data Offer from TrackDrive API and insert into table offers');
        $this->info($import_service->import(new Offer(), $provider, Period::today()) . ' Offer imported');

        // Import Did Numbers
        $this->info('Fetch Data Did Number from TrackDrive API and insert into table did_numbers');
        $this->info($import_service->import(new DidNumber(), $provider, Period::today()) . ' Did imported');
    }
}
