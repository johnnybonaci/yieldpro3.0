<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadLog extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $fillable = [
        'lead_id',
        'status',
        'log',
        'phone_id',
        'provider_id',
    ];
}
