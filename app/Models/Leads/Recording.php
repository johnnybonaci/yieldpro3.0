<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use App\Enums\TranscriptStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recording extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'url',
        'record',
        'transcript',
        'multiple',
        'qa_status',
        'qa_td_status',
        'convertion_id',
        'date_history',
        'status',
        'billable',
        'insurance',
    ];

    protected $casts = [
        'status' => TranscriptStatusEnum::class,
    ];

    /**
     * Summary of record.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(Convertion::class, 'id', 'id');
    }

    public function convertion(): BelongsTo
    {
        return $this->belongsTo(Convertion::class, 'id', 'id');
    }
}
