<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Calculated risk score for a location and risk type.
 */
class RiskScore extends Model
{
    protected $fillable = [
        'location_id', 'risk_type_id', 'risk_level',
        'score', 'recommendation', 'factors', 'calculated_at',
    ];

    protected $casts = [
        'score'          => 'float',
        'factors'        => 'array',
        'calculated_at'  => 'datetime',
    ];

    const RISK_LEVELS = [
        'low'      => ['min' => 0,  'max' => 29],
        'medium'   => ['min' => 30, 'max' => 59],
        'high'     => ['min' => 60, 'max' => 84],
        'critical' => ['min' => 85, 'max' => 100],
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function riskType(): BelongsTo
    {
        return $this->belongsTo(RiskType::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public static function levelFromScore(float $score): string
    {
        if ($score >= 85) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 30) return 'medium';
        return 'low';
    }

    public function scopeLatestPerLocation($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('risk_scores')
                ->groupBy('location_id', 'risk_type_id');
        });
    }

    public function scopeAboveLevel($query, string $level)
    {
        $levels = ['low' => 0, 'medium' => 30, 'high' => 60, 'critical' => 85];
        return $query->where('score', '>=', $levels[$level] ?? 0);
    }
}
