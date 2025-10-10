<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DuplicateLeads extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => Json::class,
    ];

    /**
     * Summary of attributes.
     *
     * @var array
     */
    protected $attributes = [
        'data' => '[]',
    ];

    public $fillable = [
        'phone',
        'first_name',
        'last_name',
        'email',
        'type',
        'zip_code',
        'state',
        'ip',
        'data',
        'campaign_name_id',
        'sub_id',
        'pub_id',
    ];
}
