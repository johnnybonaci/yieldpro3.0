<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BotLeads extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $table = 'bot_leads';

    protected $primaryKey = 'phone';

    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'ip' => Json::class,
        'created_at' => 'datetime:Y-m-d H:i:s',
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
        'universal_lead_id',
        'campaign_name_id',
        'trusted_form',
        'tries',
        'pub_id',
        'sub_id5',
        'dob',
        'rejected',
        'date_history',
        'created_at',
        'updated_at',
    ];
}
