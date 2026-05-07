<?php

namespace App\Http\Controllers\API\V1;

use App\Models\RiskScore;
use App\Models\RiskType;
use App\Models\Location;
use App\Services\RiskEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Risk Scores
 * Access and trigger climate risk score calculations.
 */
class RiskScoreController extends BaseController
{
    public function __construct(private RiskEngineService $engine) {}

    /**
     * Get latest risk scores for all locations (map data)
     *
     * Returns current risk level per location for the dashboard map.
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = RiskScore::latestPerLocation()
            ->with([
                'location:id,province_id,district_id,latitude,longitude',
                'location.province:id,name',
                'location.district:id,name',
                'riskType:id,code,name',
            ]);

        // Scope to user's location if not admin/government
        if (!$user->canAccessAllZones() && $user->location_id) {
            $query->where('location_id', $user->location_id);
        }

        if ($request->risk_type) {
            $riskTypeId = RiskType::query()->where('code', $request->risk_type)->value('id');
            $query->where('risk_type_id', $riskTypeId ?? 0);
        }

        if ($request->min_level) {
            $query->aboveLevel($request->min_level);
        }

        return $this->success($query->get());
    }

    /**
     * Get risk scores for a specific location
     * @urlParam locationId integer required Location ID. Example: 1
     * @authenticated
     */
    public function forLocation(int $locationId): JsonResponse
    {
        $this->authorizeLocationAccess($locationId);

        $scores = RiskScore::where('location_id', $locationId)
            ->latestPerLocation()
            ->with(['location:id,province_id,district_id', 'location.province:id,name', 'location.district:id,name', 'riskType:id,code,name'])
            ->get();

        return $this->success($scores);
    }

    /**
     * Get risk history for a location and type
     * @urlParam locationId integer required Location ID. Example: 1
     * @urlParam riskType string required Risk type (heat|malaria|diarrhea|respiratory). Example: malaria
     * @queryParam days integer Days of history. Example: 30
     * @authenticated
     */
    public function history(int $locationId, string $riskType): JsonResponse
    {
        $this->authorizeLocationAccess($locationId);

        $days = min(request('days', 30), 90);
        $riskTypeId = RiskType::query()->where('code', $riskType)->value('id');
        $scores = RiskScore::where('location_id', $locationId)
            ->where('risk_type_id', $riskTypeId ?? 0)
            ->where('calculated_at', '>=', now()->subDays($days))
            ->orderBy('calculated_at')
            ->get(['score', 'risk_level', 'calculated_at']);

        return $this->success($scores);
    }

    /**
     * Manually trigger risk recalculation for a location (admin only)
     * @urlParam locationId integer required. Example: 1
     * @authenticated
     */
    public function recalculate(int $locationId): JsonResponse
    {
        abort_unless(request()->user()?->can('manage-risk-engine'), 403, 'Not authorized to manage risk engine');

        $location = Location::findOrFail($locationId);
        $scores   = $this->engine->calculateForLocation($location);

        return $this->success($scores, 'Risk scores recalculated');
    }

    private function authorizeLocationAccess(int $locationId): void
    {
        $user = request()->user();
        if (!$user->canAccessAllZones() && $user->location_id !== $locationId) {
            abort(403, 'Access restricted to your zone');
        }
    }
}
