<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultipleSheets implements WithMultipleSheets
{
    use Exportable;

    protected $exports;

    public function __construct(array $exports)
    {
        $this->exports = $exports;
    }

    public function sheets(): array
    {
        $sheets = [];

        array_map(function ($export) use (&$sheets) {
            $sheets[] = $export;
        }, $this->exports);

        return $sheets;
    }
}
