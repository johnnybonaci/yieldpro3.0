<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'offer_provider_id',
        'type',
        'source_url',
        'api_key',
        'provider_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Summary of did_numbers.
     */
    public function did_numbers(): HasMany
    {
        return $this->hasMany(DidNumber::class);
    }
}
