<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhoneRoomLog extends Model
{
    use HasFactory;

    public $timestamps = true;

    public $fillable = [
        'phone_room_lead_id',
        'log',
        'status',
        'phone_id',
        'phone_room_id',
        'request',
    ];

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
     * Scope a query filters.
     *
     * @param mixed $value
     */
    public function scopeFilters(Builder $query, string $field, string $operator = '=', $value = null): void
    {
        $query->when($value, function ($query) use ($field, $operator, $value) {
            if ($operator == 'IN') {
                $value = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $operator, $value);
            }
        });
    }
}
