<?php

namespace Database\Seeders;

use App\Models\ClimateData;
use App\Models\Location;
use Illuminate\Database\Seeder;

class ClimateDataSeeder extends Seeder
{
    public function run(): void
    {
        $locations = Location::query()->limit(12)->get();
        $anchor = now()->startOfDay()->setHour(12);

        foreach ($locations as $location) {
            for ($hoursAgo = 12; $hoursAgo >= 0; $hoursAgo--) {
                $baseTemp = 24 + ($location->id % 8);
                $rain = ($location->flood_risk / 100) * rand(0, 40);
                $humidity = min(95, 55 + ($location->malaria_risk_static / 4) + rand(-8, 8));
                $recordedAt = (clone $anchor)->subHours($hoursAgo);

                ClimateData::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'source' => 'openweather',
                        'recorded_at' => $recordedAt,
                        'is_forecast' => false,
                    ],
                    [
                        'temperature' => $baseTemp + rand(-4, 4),
                        'temperature_max' => $baseTemp + rand(1, 6),
                        'temperature_min' => $baseTemp - rand(3, 6),
                        'rainfall_24h' => round($rain, 2),
                        'humidity' => round($humidity, 2),
                        'wind_speed' => rand(5, 30),
                        'air_quality_index' => max(5, 120 - $location->air_quality_baseline + rand(-10, 15)),
                    ]
                );
            }
        }
    }
}
