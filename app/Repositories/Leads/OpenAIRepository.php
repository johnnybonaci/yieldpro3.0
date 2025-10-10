<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\Recording;
use App\Models\Leads\Convertion;

class OpenAIRepository
{
    public function save(Recording $recording, array $data): bool
    {
        return $recording->update($data);
    }

    public function find(int $call): ?Recording
    {
        return Recording::find($call);
    }

    public function lead(int $call): Lead
    {
        $call = Convertion::find($call);

        return $call->lead()->first();
    }
}
