<?php

namespace App\Interfaces\Leads;

use Illuminate\Support\Collection;

interface ImportRepositoryInterface
{
    public function create(Collection $data, int $quantity): int;

    public function resource(array $data, int $provider): array;
}
