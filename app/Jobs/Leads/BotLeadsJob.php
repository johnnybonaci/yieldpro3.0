<?php

namespace App\Jobs\Leads;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BotLeadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Collection $lead)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::timeout(180)->withToken('194634|Vuhts6QFq02zOIaq6NytdpgyHc8FpepWXel4nRao12e0290b')
            ->retry(3, 200)->post('http://13.52.227.82/api/v1/leads/botleads', $this->lead->toArray())->throw();
    }
}
