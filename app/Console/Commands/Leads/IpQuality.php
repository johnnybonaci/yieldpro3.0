<?php

namespace App\Console\Commands\Leads;

use Illuminate\Console\Command;
use App\Services\Leads\IpQualityService;

class IpQuality extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ip {ip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(IpQualityService $ip_quality_service)
    {
        $this->info($ip_quality_service->index($this->argument('ip')));
    }
}
