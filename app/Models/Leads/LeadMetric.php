<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadMetric extends Model
{
    use HasFactory;
    use FiltersTrait;
    use Searchable;

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

    protected $searchableColumns = ['lead_metrics.campaign_name'];
}
