<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JornayaLead extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'universal_lead_id',
        'trusted_form',
        'phone_id',
    ];

    /**
     * Summary of lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'phone_id', 'phone');
    }

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
