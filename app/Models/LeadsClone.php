<?php

namespace App\Models;

use App\Casts\Json;
use App\Models\Leads\Pub;
use App\Models\Leads\Sub;
use App\Traits\FiltersTrait;
use App\Models\Leads\Convertion;
use App\Models\Leads\LeadMetric;
use App\Models\Leads\JornayaLead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadsClone extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $table = 'leads_clone';

    protected $primaryKey = 'phone';

    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => Json::class,
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Summary of attributes.
     *
     * @var array
     */
    protected $attributes = [
        'data' => '[]',
    ];

    protected $fillable = [
        'phone',
        'first_name',
        'last_name',
        'email',
        'type',
        'zip_code',
        'state',
        'ip',
        'cpl',
        'data',
        'yp_lead_id',
        'campaign_name_id',
        'sub_id',
        'pub_id',
        'universal_lead_id',
        'trusted_form',
        'date_history',
        'created_at',
        'created_time',
        'updated_at',
    ];

    /**
     * Summary of jornaya.
     */
    public function jornaya(): HasMany
    {
        return $this->hasMany(JornayaLead::class, 'phone_id', 'phone');
    }

    /**
     * Summary of convertions.
     */
    public function convertions(): HasMany
    {
        return $this->hasMany(Convertion::class, 'phone_id', 'phone');
    }

    /**
     * Summary of metrics.
     */
    public function metrics(): BelongsTo
    {
        return $this->belongsTo(LeadMetric::class, 'campaign_name_id', 'campaign_name');
    }

    /**
     * Summary of subs.
     */
    public function subs(): BelongsTo
    {
        return $this->belongsTo(Sub::class, 'sub_id', 'id');
    }

    /**
     * Summary of pubs.
     */
    public function pubs(): BelongsTo
    {
        return $this->belongsTo(Pub::class, 'pub_id', 'id');
    }
}
