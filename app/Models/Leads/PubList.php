<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PubList extends Model
{
    use FiltersTrait;
    use HasFactory;
    use Searchable;

    public $timestamps = false;

    public $fillable = [
        'name',
        'cpl',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'cpl' => Json::class,
    ];

    protected $searchableColumns = ['CAST(id AS CHAR)', 'pub_lists.name'];

    /**
     * Summary of did_numbers.
     */
    public function did_numbers(): HasManyThrough
    {
        return $this->hasManyThrough(DidNumber::class, Pub::class);
    }

    /**
     * Summary of did_numbers.
     */
    public function offers(): HasManyThrough
    {
        return $this->hasManyThrough(Offer::class, Pub::class);
    }

    /**
     * Summary of pubs.
     */
    public function pubs(): HasMany
    {
        return $this->hasMany(Pub::class);
    }
}
