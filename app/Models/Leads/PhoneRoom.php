<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhoneRoom extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'service',
        'api_key',
        'api_user',
        'list_id',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'config' => Json::class,
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
