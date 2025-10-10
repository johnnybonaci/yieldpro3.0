<?php

namespace App\ValueObjects;

use Carbon\Carbon;

class Period
{
    private function __construct(
        private bool $isHourly,
        private Carbon $from,
        private Carbon $to,
    ) {
    }

    public static function pastHour(): self
    {
        $now = Carbon::now()->subHours()->startOfHour();
        $end = Carbon::now()->endOfHour();

        return new self(
            true,
            $now,
            $end,
        );
    }

    public static function today($fecha = null): self
    {
        $today = $fecha ? Carbon::create($fecha) : Carbon::today();

        return new self(
            false,
            $today,
            $today->clone()->endOfDay(),
        );
    }

    public static function range($from, $to, $isHourly = false): self
    {
        return new self(
            $isHourly,
            Carbon::create($from),
            Carbon::create($to),
        );
    }

    public function isHourly(): bool
    {
        return $this->isHourly;
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function to(): Carbon
    {
        return $this->to;
    }
}
