<?php

namespace App\Http\Controllers\API\V1;

use App\Services\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SymptomReport;
use App\Models\User;

/**
 * @group Dashboard
 * KPIs, analytics and map data for the web dashboard.
 */
class DashboardController extends BaseController
{
    public function __construct(private DashboardAnalyticsService $analytics) {}

    /**
     * Get dashboard overview KPIs
     *
     * Returns aggregated metrics: families registered, alerts sent, active risks, etc.
     * @authenticated
     * @queryParam location_id integer Filter to a specific location. Example: 1
     */
    public function overview(Request $request): JsonResponse
    {
        $user       = $request->user();
        $locationId = $user->canAccessAllZones() ? $request->location_id : $user->location_id;

        return $this->success($this->analytics->getOverview($locationId));
    }

    /**
     * Get risk map data
     *
     * Returns latest risk scores per location with coordinates for the Mapbox GL map.
     * @authenticated
     */
    public function riskMap(Request $request): JsonResponse
    {
        $user       = $request->user();
        $locationId = $user->canAccessAllZones() ? null : $user->location_id;

        return $this->success($this->analytics->getRiskMapData($locationId));
    }

    /**
     * Get KPIs for a specific location
     * @urlParam locationId integer required Location ID. Example: 1
     * @authenticated
     */
    public function kpis(int $locationId): JsonResponse
    {
        $user = request()->user();
        if (!$user->canAccessAllZones() && $user->location_id !== $locationId) {
            abort(403);
        }

        return $this->success($this->analytics->getKPIs($locationId));
    }

    /**
     * Get symptom surveillance heatmap
     *
     * Returns community-reported symptoms aggregated by location for the last 30 days.
     * @authenticated
     * @queryParam location_id integer Filter by location. Example: 1
     */
    public function symptomHeatmap(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = SymptomReport::with([
                'location:id,province_id,district_id,latitude,longitude',
                'location.province:id,name',
                'location.district:id,name',
            ])
            ->where('created_at', '>=', now()->subDays(30))
            ->when(!$user->canAccessAllZones(), fn($q) => $q->where('location_id', $user->location_id));

        return $this->success($query->get());
    }

    /**
     * Get family registration statistics
     * @authenticated
     */
    public function familyStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = User::activeSubscribers()
            ->when(!$user->canAccessAllZones(), fn($q) => $q->where('location_id', $user->location_id))
            ->selectRaw("channel, COUNT(*) as count")
            ->groupBy('channel')
            ->pluck('count', 'channel');

        return $this->success([
            'by_channel'     => $stats,
            'total'          => $stats->sum(),
            'by_province'    => $this->analytics->getOverview()['families_by_province'],
        ]);
    }
}
