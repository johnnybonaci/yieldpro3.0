<?php

namespace App\Models\Leads;

use App\Traits\FiltersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediaAlphaConfig extends Model
{
    use HasFactory;
    use FiltersTrait;

    protected $table = 'media_alpha_configs';

    protected $fillable = [
        'name',
        'api_token',
        'placement_id',
        'version',
        'base_url',
        'ping_endpoint',
        'post_endpoint',
        'source_url',
        'tcpa_config',
        'default_mapping',
        'active',
    ];

    protected $casts = [
        'tcpa_config' => 'array',
        'default_mapping' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Scope para obtener configuraciones activas.
     * @param mixed $query
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Obtener configuración por placement_id.
     * @param mixed $query
     * @param mixed $placementId
     */
    public function scopeByPlacementId($query, $placementId)
    {
        return $query->where('placement_id', $placementId);
    }

    /**
     * Obtener la configuración TCPA por defecto.
     */
    public function getDefaultTcpaConfig()
    {
        return $this->tcpa_config;
    }

    /**
     * Obtener URLs completas para los endpoints.
     */
    public function getPingUrlAttribute()
    {
        return rtrim($this->base_url, '/') . $this->ping_endpoint;
    }

    public function getPostUrlAttribute()
    {
        return rtrim($this->base_url, '/') . $this->post_endpoint;
    }
}
