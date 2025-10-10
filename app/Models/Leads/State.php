<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class State extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    public $fillable = [
        'state',
        'description',
    ];
}
