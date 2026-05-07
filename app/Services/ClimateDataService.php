<?php

namespace App\Services;

use App\Models\ClimateData;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Fetches climate data from OpenWeather, Tomorrow.io and INAM.
 */
class ClimateDataService
{
    private string $owApiKey;
    private string $tomorrowApiKey;

    public function __construct()
    {
        $this->owApiKey      = config('services.openweather.key');
        $this->tomorrowApiKey = config('services.tomorrow.key');
    }

    /**
     * Fetch and store climate data for all locations.
     */
    public function fetchAllLocations(): void
    {
        Location::chunk(20, function ($locations) {
            foreach ($locations as $location) {
                $this->fetchForLocation($location);
                usleep(200000); // 0.2s delay to respect rate limits
            }
        });
    }

    public function fetchForLocation(Location $location): ?ClimateData
    {
        if (!$location->latitude || !$location->longitude) {
            return null;
        }

        try {
            $data = $this->fetchOpenWeather($location);
            if (!$data) {
                $data = $this->fetchTomorrow($location);
            }

            if ($data) {
                return ClimateData::create(array_merge($data, [
                    'location_id' => $location->id,
                    'recorded_at' => now(),
                    'is_forecast' => false,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error("Climate fetch error for location #{$location->id}: " . $e->getMessage());
        }

        return null;
    }

    private function fetchOpenWeather(Location $location): ?array
    {
        $cacheKey = "ow_{$location->id}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, 3600, function () use ($location) {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat'   => $location->latitude,
                'lon'   => $location->longitude,
                'appid' => $this->owApiKey,
                'units' => 'metric',
            ]);

            if (!$response->successful()) return null;
            $d = $response->json();

            return [
                'temperature'       => $d['main']['temp'] ?? null,
                'temperature_max'   => $d['main']['temp_max'] ?? null,
                'temperature_min'   => $d['main']['temp_min'] ?? null,
                'humidity'          => $d['main']['humidity'] ?? null,
                'wind_speed'        => $d['wind']['speed'] ?? null,
                'rainfall_24h'      => $d['rain']['1h'] ?? $d['rain']['3h'] ?? 0,
                'air_quality_index' => null,
                'source'            => 'openweather',
            ];
        });
    }

    private function fetchTomorrow(Location $location): ?array
    {
        $cacheKey = "tomorrow_{$location->id}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, 3600, function () use ($location) {
            $response = Http::timeout(10)->get('https://api.tomorrow.io/v4/weather/realtime', [
                'location' => "{$location->latitude},{$location->longitude}",
                'apikey'   => $this->tomorrowApiKey,
                'units'    => 'metric',
            ]);

            if (!$response->successful()) return null;
            $v = $response->json('data.values', []);

            return [
                'temperature'       => $v['temperature'] ?? null,
                'temperature_max'   => $v['temperatureApparent'] ?? null,
                'temperature_min'   => null,
                'humidity'          => $v['humidity'] ?? null,
                'wind_speed'        => $v['windSpeed'] ?? null,
                'rainfall_24h'      => $v['precipitationIntensity'] ?? 0,
                'air_quality_index' => $v['particulateMatter25'] ?? null,
                'source'            => 'tomorrow',
            ];
        });
    }

    /**
     * Get 5-day forecast for a location.
     */
    public function getForecast(Location $location): array
    {
        $cacheKey = "forecast_{$location->id}_" . now()->format('Ymd');

        return Cache::remember($cacheKey, 21600, function () use ($location) {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/forecast', [
                'lat'   => $location->latitude,
                'lon'   => $location->longitude,
                'appid' => $this->owApiKey,
                'units' => 'metric',
                'cnt'   => 40,
            ]);

            if (!$response->successful()) return [];

            return collect($response->json('list', []))
                ->map(fn($item) => [
                    'datetime'    => $item['dt_txt'],
                    'temperature' => $item['main']['temp'],
                    'humidity'    => $item['main']['humidity'],
                    'rainfall'    => $item['rain']['3h'] ?? 0,
                    'wind_speed'  => $item['wind']['speed'],
                ])
                ->toArray();
        });
    }
}
