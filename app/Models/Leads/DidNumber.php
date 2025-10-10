<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DidNumber extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    public $incrementing = false;

    public $fillable = [
        'id',
        'description',
        'campaign_name',
        'sub_id',
        'pub_id',
        'traffic_source_id',
        'offer_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Summary of traffic_sources.
     */
    public function traffic_sources(): BelongsTo
    {
        return $this->belongsTo(TrafficSource::class, 'traffic_source_id', 'id');
    }

    /**
     * Summary of offers.
     */
    public function offers(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'id');
    }

    /**
     * Summary of offers.
     */
    public function pubs(): BelongsTo
    {
        return $this->belongsTo(Pub::class, 'pub_id', 'id');
    }

    /**
     * Summary of offers.
     */
    public function subs(): BelongsTo
    {
        return $this->belongsTo(Sub::class, 'sub_id', 'id');
    }
}
