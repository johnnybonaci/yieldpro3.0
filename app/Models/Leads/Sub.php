<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sub extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    public $fillable = [
        'sub_id',
    ];

    /**
     * Summary of did_numbers.
     */
    public function did_numbers(): HasMany
    {
        return $this->hasMany(DidNumber::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            return $query->where(
                DB::raw('LOWER(subs.sub_id)'), 'like', '%' . strtolower($search) . '%'
            );
        });
    }
}
