<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LiveLead extends Model
{
    use Prunable;
    use FiltersTrait;

    protected $guarded = [];

    protected $casts = [
        'updated_at_date' => 'date',
        'convertion_updated_at_date' => 'date',
        'convertion_created_at' => 'datetime',
        'convertion_updated_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<=', now()->subMonths(2));
    }

    public function latestCall(): HasOne
    {
        return $this->hasOne(Call::class, 'phone_id', 'phone')->latestOfMany('created_at');
    }
}
