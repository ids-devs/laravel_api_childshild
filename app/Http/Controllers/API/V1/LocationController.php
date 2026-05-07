<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Province;
use App\Models\District;
use App\Models\Location;
use App\Models\HealthFacility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Locations
 * Manage geographic locations (provinces, districts, localities).
 */
class LocationController extends BaseController
{
    /**
     * List all locations
     *
     * Returns provinces, districts, localities hierarchy for USSD/UI selection.
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $query = Location::query();

        if ($request->province_id) {
            $query->byProvince((int) $request->province_id);
        }
        if ($request->district_id) {
            $query->byDistrict((int) $request->district_id);
        }

        $locations = $query->with(['province:id,name', 'district:id,name'])
            ->get(['id', 'province_id', 'district_id', 'locality', 'latitude', 'longitude']);
        return $this->success($locations);
    }

    /**
     * List distinct provinces
     */
    public function provinces(): JsonResponse
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name', 'region']);
        return $this->success($provinces);
    }

    /**
     * List districts for a province
     * @urlParam provinceId integer required Province ID. Example: 1
     */
    public function districts(int $provinceId): JsonResponse
    {
        $districts = District::query()->where('province_id', $provinceId)->orderBy('name')->get(['id', 'name']);
        return $this->success($districts);
    }

    /**
     * Get location details with current risk scores
     * @urlParam id integer required Location ID. Example: 1
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $location = Location::with(['riskScores' => fn($q) => $q->latestPerLocation()])
            ->findOrFail($id);

        return $this->success($location);
    }

    /**
     * Create location (admin only)
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-locations');

        $request->validate([
            'province_id'   => 'required|integer|exists:provinces,id',
            'district_id'   => 'required|integer|exists:districts,id',
            'locality'   => 'nullable|string',
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
            'malaria_risk_static'    => 'nullable|numeric|between:0,100',
            'sanitation_score'       => 'nullable|numeric|between:0,100',
            'flood_risk'             => 'nullable|numeric|between:0,100',
            'air_quality_baseline'   => 'nullable|numeric|between:0,100',
            'health_coverage_score'  => 'nullable|numeric|between:0,100',
            'is_coastal'  => 'boolean',
            'is_urban'    => 'boolean',
        ]);

        $location = Location::create($request->all());
        return $this->success($location, 'Location created', 201);
    }

    /**
     * Get nearby health facilities
     * @urlParam id integer required Location ID. Example: 1
     * @queryParam limit integer Max facilities to return. Example: 3
     */
    public function nearbyFacilities(int $id): JsonResponse
    {
        $location = Location::findOrFail($id);
        $facilities = HealthFacility::nearest($location->latitude, $location->longitude, request('limit', 3));
        return $this->success($facilities);
    }
}
