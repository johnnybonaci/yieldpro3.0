<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasFactory;
    use FiltersTrait;

    protected $fillable = [
        'name',
        'service',
        'url',
        'api_key',
        'active',
    ];

    public $timestamps = true;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
