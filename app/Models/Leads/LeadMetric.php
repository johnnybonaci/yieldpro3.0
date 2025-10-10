<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadMetric extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $table = 'lead_metrics';

    protected $primaryKey = 'campaign_name';

    public $incrementing = false;

    public $fillable = [
        'campaign_name',
        'utm_source',
        'utm_medium',
        'utm_content',
    ];

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            return $query->where(
                DB::raw('LOWER(lead_metrics.campaign_name)'), 'like', '%' . strtolower($search) . '%'
            );
        });
    }
}
