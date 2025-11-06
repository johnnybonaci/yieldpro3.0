<?php

namespace App\Models\Leads;

use App\Casts\Json;
use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoryLeads extends Model
{
    use HasFactory;
    use FiltersTrait;

    public $timestamps = true;

    protected $table = 'history_leads';

    protected $fillable = [
        'before_h',
        'after_h',
        'phone_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'before_h' => Json::class,
        'after_h' => Json::class,
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'phone_id', 'phone');
    }
}
