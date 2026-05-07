<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Location model representing a geographic area.
 *
 * @property int $id
 * @property int $province_id
 * @property int $district_id
 * @property string|null $locality
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float $malaria_risk_static
 * @property float $sanitation_score
 * @property float $flood_risk
 * @property float $air_quality_baseline
 * @property float $health_coverage_score
 * @property bool $is_coastal
 * @property bool $is_urban
 */
class Location extends Model
{
    protected $fillable = [
        'province_id', 'district_id', 'locality',
        'latitude', 'longitude',
        'malaria_risk_static', 'sanitation_score',
        'flood_risk', 'air_quality_baseline',
        'health_coverage_score', 'is_coastal', 'is_urban',
    ];

    protected $casts = [
        'latitude'              => 'float',
        'longitude'             => 'float',
        'malaria_risk_static'   => 'float',
        'sanitation_score'      => 'float',
        'flood_risk'            => 'float',
        'air_quality_baseline'  => 'float',
        'health_coverage_score' => 'float',
        'is_coastal'            => 'boolean',
        'is_urban'              => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function climateData(): HasMany
    {
        return $this->hasMany(ClimateData::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(RiskScore::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function symptomReports(): HasMany
    {
        return $this->hasMany(SymptomReport::class);
    }

    public function healthFacilities(): HasMany
    {
        return $this->hasMany(HealthFacility::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeByProvince($query, int $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByDistrict($query, int $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /** Find locations within radius (km) of given coordinates using PostGIS */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 50)
    {
        return $query->whereRaw(
            'ST_DWithin(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $radiusKm * 1000]
        );
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return implode(', ', array_filter([
            $this->locality,
            $this->district?->name,
            $this->province?->name,
        ]));
    }
}
