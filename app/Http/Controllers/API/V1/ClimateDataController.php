<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ClimateData;
use App\Models\Location;
use App\Services\ClimateDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Climate Data
 * Access real-time and forecast climate data per location.
 */
class ClimateDataController extends BaseController
{
    public function __construct(private ClimateDataService $climateService) {}

    /**
     * Get latest climate data for a location
     * @urlParam locationId integer required Location ID. Example: 1
     * @authenticated
     */
    public function latest(int $locationId): JsonResponse
    {
        $data = ClimateData::forLocation($locationId)->currentOnly()->latest()->first();

        if (!$data) {
            // Try to fetch fresh data
            $location = Location::findOrFail($locationId);
            $data     = $this->climateService->fetchForLocation($location);
        }

        return $this->success($data);
    }

    /**
     * Get 5-day forecast for a location
     * @urlParam locationId integer required Location ID. Example: 1
     * @authenticated
     */
    public function forecast(int $locationId): JsonResponse
    {
        $location = Location::findOrFail($locationId);
        $forecast = $this->climateService->getForecast($location);
        return $this->success($forecast);
    }

    /**
     * Get climate history for a location
     * @urlParam locationId integer required. Example: 1
     * @queryParam days integer History days (max 30). Example: 7
     * @authenticated
     */
    public function history(int $locationId): JsonResponse
    {
        $days = min(request('days', 7), 30);

        $data = ClimateData::forLocation($locationId)
            ->currentOnly()
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at')
            ->get();

        return $this->success($data);
    }

    /**
     * Trigger manual climate data refresh (admin only)
     * @authenticated
     */
    public function refresh(int $locationId): JsonResponse
    {
        $this->authorize('manage-climate-data');

        $location = Location::findOrFail($locationId);
        $data     = $this->climateService->fetchForLocation($location);

        return $this->success($data, 'Climate data refreshed');
    }
}
