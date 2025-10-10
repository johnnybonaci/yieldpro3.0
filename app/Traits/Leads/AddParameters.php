<?php

namespace App\Traits\Leads;

use App\ValueObjects\Period;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

trait AddParameters
{
    /**
     * Summary of import.
     */
    abstract public function import(Model $models, int $provider, Period $period): int;

    /**
     * Summary of make.
     */
    public static function make(...$parameters): array
    {
        [$columns, $per_page, $page] = $parameters;

        return [
            'columns' => $columns,
            'per_page' => $per_page,
            'page' => $page,
        ];
    }

    /**
     * Summary of listVar.
     */
    public static function list(Model $models): array
    {
        $table = $models->getTable();

        return [
            $table === 'did_numbers' ? 'phone_numbers' : $table,
            Config::get('services.trackdrive.' . $table . '.columns'),
            1,
            Config::get('services.trackdrive.' . $table . '.fields'),
            Config::get('services.trackdrive.' . $table . '.repository'),
            Config::get('services.trackdrive.' . $table . '.collects'),
        ];
    }
}
