<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Buyer extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'revenue',
        'buyer_provider_id',
        'provider_id',
        'created_at',
        'updated_at',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            return $query->where(
                DB::raw('LOWER(buyers.name)'), 'like', '%' . strtolower($search) . '%'
            );
        });
    }
}
