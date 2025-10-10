<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

class Call extends Model
{
    use Prunable;
    use FiltersTrait;

    protected $guarded = [];

    protected $casts = [
        'outside' => 'boolean',
        'td_created_at_date' => 'date',
        'td_created_at' => 'datetime',
        'td_updated_at' => 'datetime',
        'ai_analysis' => 'array',
        'ai_qa_analysis' => 'array',
        'td_qa_status' => 'array',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<=', now()->subMonths(2));
    }
}
