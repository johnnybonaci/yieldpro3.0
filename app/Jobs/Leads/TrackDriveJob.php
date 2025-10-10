<?php

namespace App\Jobs\Leads;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Leads\TrackDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Leads\TrackDriveRepository;

class TrackDriveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $provider;

    /**
     * Create a new job instance.
     */
    public function __construct(private Collection $lead, Model $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Execute the job.
     */
    public function handle(TrackDriveRepository $track_drive_repository, TrackDriveService $track_drive_service): void
    {
        $offers = $track_drive_repository->setFields($this->lead);
        $leads = $this->lead->toArray();
        $leads['offers_data'] = $offers['offers'];
        $leads['provider_id'] = $this->provider->id;
        $data = $track_drive_repository->resource($leads);
        // Exception Medicare Amigos send data to TrackDrive
        $track_drive_service->create(__toException101($data->toArray()), $this->provider);
    }
}
