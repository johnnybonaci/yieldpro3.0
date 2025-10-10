<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallsPhoneRoom extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $table = 'calls_phone_rooms';

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
        'data',
        'type',
        'created_at',
        'updated_at',
    ];
}
