<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pub extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    public $fillable = [
        'offer_id',
        'pub_list_id',
        'setup',
        'interleave',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'setup' => Json::class,
        'interleave' => Json::class,
    ];

    /**
     * Summary of did_numbers.
     */
    public function did_numbers(): HasMany
    {
        return $this->hasMany(DidNumber::class);
    }

    /**
     * Summary of offers.
     */
    public function offers(): BelongsTo
    {
        return $this->BelongsTo(Offer::class, 'offer_id', 'id');
    }

    /**
     * Summary of offers.
     */
    public function pub_lists(): BelongsTo
    {
        return $this->BelongsTo(PubList::class, 'pub_list_id', 'id');
    }
}
