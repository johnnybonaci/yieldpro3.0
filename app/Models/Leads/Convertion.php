<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Convertion extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'outside',
        'status',
        'answered',
        'revenue',
        'cpl',
        'durations',
        'calls',
        'converted',
        'terminating_phone',
        'did_number_id',
        'phone_id',
        'buyer_id',
        'traffic_source_id',
        'offer_id',
        'date_history',
        'created_at',
        'updated_at',
    ];

    /**
     * Summary of lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'phone_id', 'phone');
    }

    /**
     * Summary of record.
     */
    public function record(): HasMany
    {
        return $this->hasMany(Recording::class, 'id', 'id');
    }

    public function trafficSource(): BelongsTo
    {
        return $this->belongsTo(TrafficSource::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
