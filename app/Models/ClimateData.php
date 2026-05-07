<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Climate data point from OpenWeather, Tomorrow.io or INAM.
 */
class ClimateData extends Model
{
    protected $fillable = [
        'location_id', 'temperature', 'temperature_max', 'temperature_min',
        'rainfall_24h', 'humidity', 'wind_speed', 'air_quality_index',
        'source', 'recorded_at', 'is_forecast',
    ];

    protected $casts = [
        'temperature'        => 'float',
        'temperature_max'    => 'float',
        'temperature_min'    => 'float',
        'rainfall_24h'       => 'float',
        'humidity'           => 'float',
        'wind_speed'         => 'float',
        'air_quality_index'  => 'float',
        'recorded_at'        => 'datetime',
        'is_forecast'        => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeCurrentOnly($query)
    {
        return $query->where('is_forecast', false);
    }

    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
