<?php

namespace App\Jobs\Leads;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Leads\PhoneRoomRepository;

class ConvosoCallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $phone_room;

    /**
     * Create a new job instance.
     */
    public function __construct(private Collection $lead, Model $phone_room)
    {
        $this->phone_room = $phone_room;
    }

    /**
     * Execute the job.
     */
    public function handle(PhoneRoomRepository $phone_room_repository): void
    {
        $phone_room_repository->setFields($this->lead);
        $data = $phone_room_repository->resourceConvoso($this->lead, $this->phone_room);
        $service = __toClass($this->phone_room->service);
        $service->verify($data);
    }
}
