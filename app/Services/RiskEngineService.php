<?php

namespace App\Services;

use App\Models\ClimateData;
use App\Models\RiskType;
use App\Models\Location;
use App\Models\RiskScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core risk calculation engine.
 * Combines static vulnerability scores with real-time climate data.
 *
 * Score formula: final_score = f(static_vulnerability × dynamic_climate_data)
 * Levels: Low <30 | Medium 30-59 | High 60-84 | Critical ≥85
 */
class RiskEngineService
{
    /**
     * Calculate and persist risk scores for all active locations.
     */
    public function calculateAllLocations(): void
    {
        Location::chunk(50, function ($locations) {
            foreach ($locations as $location) {
                $this->calculateForLocation($location);
            }
        });
    }

    /**
     * Calculate all risk types for a single location.
     */
    public function calculateForLocation(Location $location): array
    {
        $climate = ClimateData::forLocation($location->id)
            ->currentOnly()
            ->latest()
            ->first();

        if (!$climate) {
            Log::warning("No climate data for location #{$location->id}");
            return [];
        }

        $scores = [];
        $riskTypes = RiskType::query()->get(['id', 'code']);

        if ($riskTypes->isEmpty()) {
            Log::warning('No risk types configured. Seed risk_types table first.');
            return [];
        }

        foreach ($riskTypes as $riskType) {
            $scores[$riskType->code] = $this->calculateScore($riskType, $location, $climate);
        }

        return $scores;
    }

    // ─── Heat Risk ────────────────────────────────────────────────────────

    private function calculateHeatScore(Location $location, ClimateData $climate): array
    {
        $base = 0;

        // Dynamic: temperature thresholds
        $temp = $climate->temperature_max ?? $climate->temperature;
        if ($temp !== null) {
            if ($temp >= 40)      $base = 100;
            elseif ($temp >= 39)  $base = 70;
            elseif ($temp >= 36)  $base = 40;
            elseif ($temp >= 32)  $base = 0;
        }

        // Static modifier: coastal areas have higher humidity risk
        $staticWeight = $location->is_coastal ? 10 : 0;

        $score = min(100, $base + $staticWeight);

        return [
            'score'   => $score,
            'factors' => [
                'temperature_max' => $temp,
                'base_score'      => $base,
                'coastal_bonus'   => $staticWeight,
            ],
            'recommendation' => $this->heatRecommendation($score),
        ];
    }

    // ─── Malaria Risk ─────────────────────────────────────────────────────

    private function calculateMalariaScore(Location $location, ClimateData $climate): array
    {
        $base = 0;

        // Dynamic: rainfall ≥20mm/48h + humidity ≥70%
        $rain = $climate->rainfall_24h ?? 0;
        $humidity = $climate->humidity ?? 0;

        if ($rain >= 20) $base = 60;
        if ($humidity >= 70) $base += 10;

        // Static: malaria endemic zone multiplier
        $staticMult = $location->malaria_risk_static > 70 ? 1.3 : 1.0;
        $score = min(100, $base * $staticMult);

        return [
            'score'   => round($score, 2),
            'factors' => [
                'rainfall_24h'      => $rain,
                'humidity'          => $humidity,
                'malaria_risk_zone' => $location->malaria_risk_static,
                'multiplier'        => $staticMult,
            ],
            'recommendation' => $this->malariaRecommendation($score),
        ];
    }

    // ─── Diarrhea Risk ────────────────────────────────────────────────────

    private function calculateDiarrheaScore(Location $location, ClimateData $climate): array
    {
        $base = 0;

        $rain = $climate->rainfall_24h ?? 0;
        $temp = $climate->temperature ?? 0;

        // Intense rain + high temp = water contamination risk
        if ($rain > 20 && $temp > 28) $base = 55;
        if ($rain > 50) $base += 20;

        // Static: poor sanitation multiplier
        $sanitationMult = $location->sanitation_score < 30 ? 1.4 : 1.0;
        $score = min(100, $base * $sanitationMult);

        return [
            'score'   => round($score, 2),
            'factors' => [
                'rainfall_24h'     => $rain,
                'temperature'      => $temp,
                'sanitation_score' => $location->sanitation_score,
                'multiplier'       => $sanitationMult,
            ],
            'recommendation' => $this->diarrheaRecommendation($score),
        ];
    }

    // ─── Respiratory Risk ─────────────────────────────────────────────────

    private function calculateRespiratoryScore(Location $location, ClimateData $climate): array
    {
        $base = 0;

        $wind = $climate->wind_speed ?? 0;
        $humidity = $climate->humidity ?? 100;
        $aqi = $climate->air_quality_index ?? 0;

        // Dry season + wind = dust/pollution
        if ($wind > 30 && $humidity < 40) $base = 50;
        if ($aqi > 100) $base += 30;

        // Static: industrial/coastal zones have worse baseline
        $staticBonus = ($location->is_urban || $location->is_coastal) ? 15 : 0;
        $score = min(100, $base + $staticBonus);

        return [
            'score'   => $score,
            'factors' => [
                'wind_speed'       => $wind,
                'humidity'         => $humidity,
                'air_quality_index'=> $aqi,
                'urban_coastal'    => $staticBonus,
            ],
            'recommendation' => $this->respiratoryRecommendation($score),
        ];
    }

    // ─── Orchestration ────────────────────────────────────────────────────

    private function calculateScore(RiskType $riskType, Location $location, ClimateData $climate): RiskScore
    {
        $result = match($riskType->code) {
            'heat'        => $this->calculateHeatScore($location, $climate),
            'malaria'     => $this->calculateMalariaScore($location, $climate),
            'diarrhea'    => $this->calculateDiarrheaScore($location, $climate),
            'respiratory' => $this->calculateRespiratoryScore($location, $climate),
            default       => $this->calculateHeatScore($location, $climate),
        };

        return RiskScore::updateOrCreate(
            [
                'location_id' => $location->id,
                'risk_type_id' => $riskType->id,
                'calculated_at' => now()->startOfHour(),
            ],
            [
                'risk_level'     => RiskScore::levelFromScore($result['score']),
                'score'          => $result['score'],
                'recommendation' => $result['recommendation'],
                'factors'        => $result['factors'],
            ]
        );
    }

    // ─── Recommendations ──────────────────────────────────────────────────

    private function heatRecommendation(float $score): string
    {
        if ($score >= 85) return 'CRÍTICO: Calor extremo. Mantenha crianças em local fresco. Ofereça líquidos a cada 30 min. Procure unidade sanitária se criança ficou prostrada.';
        if ($score >= 60) return 'ALTO: Calor intenso previsto. Evite exposição ao sol entre 11h-15h. Ofereça mais água às crianças. Vista roupas leves.';
        if ($score >= 30) return 'MÉDIO: Temperatura elevada. Mantenha crianças hidratadas. Use chapéu ao sair.';
        return 'Risco baixo de calor.';
    }

    private function malariaRecommendation(float $score): string
    {
        if ($score >= 85) return 'CRÍTICO: Risco muito alto de malária. Durma sempre com rede mosquiteira. Elimine água parada. Procure unidade sanitária se criança tiver febre.';
        if ($score >= 60) return 'ALTO: Chuvas recentes aumentam malária. Use rede mosquiteira tratada. Elimine recipientes com água parada.';
        if ($score >= 30) return 'MÉDIO: Mantenha rede mosquiteira e elimine água estagnada.';
        return 'Risco baixo de malária.';
    }

    private function diarrheaRecommendation(float $score): string
    {
        if ($score >= 85) return 'CRÍTICO: Risco alto de contaminação da água. Use água fervida ou tratada. Lave mãos com frequência. Leve criança com diarreia à unidade sanitária.';
        if ($score >= 60) return 'ALTO: Chuvas intensas podem contaminar fontes de água. Ferva ou trate a água de consumo.';
        if ($score >= 30) return 'MÉDIO: Lave sempre as mãos antes de preparar alimentos.';
        return 'Risco baixo de diarreia.';
    }

    private function respiratoryRecommendation(float $score): string
    {
        if ($score >= 85) return 'CRÍTICO: Qualidade do ar muito má. Evite que crianças brinquem ao ar livre. Use máscara se necessário sair.';
        if ($score >= 60) return 'ALTO: Poeira/poluição elevada. Mantenha janelas fechadas. Evite exposição de crianças ao ar livre.';
        if ($score >= 30) return 'MÉDIO: Qualidade do ar reduzida. Limite tempo ao ar livre.';
        return 'Boa qualidade do ar.';
    }
}
