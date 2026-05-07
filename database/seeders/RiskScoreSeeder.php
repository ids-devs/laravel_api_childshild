<?php

namespace Database\Seeders;

use App\Models\ClimateData;
use App\Models\Location;
use App\Models\RiskScore;
use App\Models\RiskType;
use Illuminate\Database\Seeder;

class RiskScoreSeeder extends Seeder
{
    public function run(): void
    {
        $riskTypes = RiskType::query()->pluck('id', 'code');
        $locations = Location::query()->limit(12)->get();
        $calculatedAt = now()->startOfDay()->setHour(13);

        foreach ($locations as $location) {
            $latestClimate = ClimateData::query()
                ->where('location_id', $location->id)
                ->where('is_forecast', false)
                ->latest('recorded_at')
                ->first();

            if (! $latestClimate) {
                continue;
            }

            $scores = [
                'heat' => min(100, ($latestClimate->temperature * 2.4) + ($latestClimate->humidity * 0.2)),
                'malaria' => min(100, ($latestClimate->humidity * 0.7) + ($location->malaria_risk_static * 0.5)),
                'diarrhea' => min(100, ((100 - $location->sanitation_score) * 0.9) + ($latestClimate->rainfall_24h * 0.8)),
                'respiratory' => min(100, ((100 - $location->air_quality_baseline) * 0.6) + ($latestClimate->air_quality_index * 0.4)),
            ];

            foreach ($scores as $code => $rawScore) {
                if (! isset($riskTypes[$code])) {
                    continue;
                }

                $score = round($rawScore, 2);
                RiskScore::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'risk_type_id' => $riskTypes[$code],
                        'calculated_at' => $calculatedAt,
                    ],
                    [
                        'score' => $score,
                        'risk_level' => RiskScore::levelFromScore($score),
                        'recommendation' => 'Keep vulnerable households informed and monitor local health capacity.',
                        'factors' => [
                            'temperature' => $latestClimate->temperature,
                            'humidity' => $latestClimate->humidity,
                            'rainfall_24h' => $latestClimate->rainfall_24h,
                            'static' => [
                                'malaria_risk_static' => $location->malaria_risk_static,
                                'sanitation_score' => $location->sanitation_score,
                                'air_quality_baseline' => $location->air_quality_baseline,
                            ],
                        ],
                    ]
                );
            }
        }
    }
}
