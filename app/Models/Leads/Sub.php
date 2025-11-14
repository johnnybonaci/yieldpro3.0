<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sub extends Model
{
    use HasFactory;
    use FiltersTrait;
    use Searchable;

    public $timestamps = false;

    public $fillable = [
        'sub_id',
    ];

    protected $searchableColumns = ['subs.sub_id'];

    /**
     * Summary of did_numbers.
     */
    public function did_numbers(): HasMany
    {
        return $this->hasMany(DidNumber::class);
    }
}
