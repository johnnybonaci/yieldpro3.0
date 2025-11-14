<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Buyer extends Model
{
    use HasFactory;
    use FiltersTrait;
    use Searchable;

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

    protected $searchableColumns = ['buyers.name'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
