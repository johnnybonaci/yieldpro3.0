<?php

namespace App\Interfaces\Leads;

interface PostingServiceInterface
{
    public function submit(array $data): bool;
}
