<?php

namespace App\Interfaces\Leads;

use App\ValueObjects\Period;
use Illuminate\Database\Eloquent\Model;

interface ImportInterface
{
    public function import(Model $models, int $provider, Period $period): int;
}
