<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthFacility extends Model
{
    protected $fillable = [
        'name', 'type', 'location_id', 'address',
        'phone', 'latitude', 'longitude', 'is_active',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }

    /** Find N nearest facilities to given coordinates */
    public static function nearest(float $lat, float $lng, int $limit = 3)
    {
        return static::active()
            ->selectRaw("*, ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_m", [$lng, $lat])
            ->orderByRaw("ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)", [$lng, $lat])
            ->limit($limit)
            ->get();
    }
}
