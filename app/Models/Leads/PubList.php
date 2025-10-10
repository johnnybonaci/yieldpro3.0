<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PubList extends Model
{
    use FiltersTrait;
    use HasFactory;

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

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            $search = strtolower($search);

            return $query
                ->where(DB::raw('CAST(id AS CHAR)'), 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('LOWER(pub_lists.name)'), 'LIKE', '%' . $search . '%');
        });
    }
}
