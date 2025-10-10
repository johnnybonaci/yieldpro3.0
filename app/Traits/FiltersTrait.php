<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait FiltersTrait
{
    /**
     * Scope a query to Order Default.
     *
     * @param mixed $value
     */
    public function scopeOrderDefault(Builder $query, string $value)
    {
        $query->orderByDesc($value);
    }

    /**
     * Scope a query to Order Default ASC.
     *
     * @param mixed $value
     */
    public function scopeOrderAsc(Builder $query, string $value)
    {
        $query->orderBy($value);
    }

    /**
     * Scope a query filters.
     *
     * @param mixed $value
     */
    public function scopeFilters(Builder $query, string $field, string $operator = '=', $value = null): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

        switch (strtoupper($operator)) {
            case 'IN':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($field, $values);

                break;
            case 'EQUAL':
                $query->where($field, '=', str_replace(['%', ''], '', $value));

                break;
            default:
                if ($field === 'phone_room_logs.status' && $value == '2') {
                    $value = '0';
                }
                $query->where($field, $operator, $value);

                break;
        }
    }
}
