<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Location;
use App\Models\RiskScore;
use App\Models\SymptomReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard KPI and analytics calculations.
 */
class DashboardAnalyticsService
{
    public function getOverview(int $locationId = null): array
    {
        $cacheKey = "dashboard_overview_{$locationId}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, 900, function () use ($locationId) {
            $userQuery  = User::activeSubscribers();
            $alertQuery = Alert::query();
            $scoreQuery = RiskScore::latestPerLocation();

            if ($locationId) {
                $userQuery  = $userQuery->where('location_id', $locationId);
                $alertQuery = $alertQuery->where('location_id', $locationId);
                $scoreQuery = $scoreQuery->where('location_id', $locationId);
            }

            return [
                'total_families'         => $userQuery->count(),
                'alerts_sent_today'      => Alert::whereDate('sent_at', today())->count(),
                'active_high_risks'      => RiskScore::latestPerLocation()->aboveLevel('high')->count(),
                'symptom_reports_week'   => SymptomReport::where('created_at', '>=', now()->subWeek())->count(),
                'alerts_coverage_rate'   => $this->alertCoverageRate($locationId),
                'risk_map'               => $this->getRiskMapData($locationId),
                'top_symptoms'           => $this->getTopSymptoms($locationId),
                'alerts_by_level'        => $this->alertsByLevel(7),
                'families_by_province'   => $this->familiesByProvince(),
            ];
        });
    }

    public function getRiskMapData(?int $locationId = null): array
    {
        return RiskScore::latestPerLocation()
            ->with(['location' => fn($q) => $q->select('id', 'province_id', 'district_id', 'latitude', 'longitude'), 'location.province:id,name', 'location.district:id,name', 'riskType:id,code'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->get()
            ->groupBy('location_id')
            ->map(function ($scores) {
                $location = $scores->first()->location;
                return [
                    'location_id' => $location->id,
                    'province'    => $location->province?->name,
                    'district'    => $location->district?->name,
                    'latitude'    => $location->latitude,
                    'longitude'   => $location->longitude,
                    'risks'       => $scores->keyBy(fn($s) => $s->riskType?->code)
                        ->map(fn($s) => ['level' => $s->risk_level, 'score' => $s->score]),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getKPIs(int $locationId): array
    {
        return [
            'expected_cases'   => $this->expectedCasesByType($locationId),
            'alert_coverage'   => $this->alertCoverageRate($locationId),
            'families_reached' => $this->familiesReachedLast30Days($locationId),
            'symptom_heatmap'  => $this->symptomHeatmap($locationId),
        ];
    }

    private function alertCoverageRate(?int $locationId): float
    {
        $total  = User::activeSubscribers()->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
        $reached = DB::table('alert_deliveries')
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(30))
            ->when($locationId, function ($q) use ($locationId) {
                $q->whereIn('user_id', User::where('location_id', $locationId)->pluck('id'));
            })
            ->distinct('user_id')
            ->count();

        return $total > 0 ? round(($reached / $total) * 100, 1) : 0;
    }

    private function alertsByLevel(int $days): array
    {
        return Alert::selectRaw('risk_level, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();
    }

    private function familiesByProvince(): array
    {
        return User::activeSubscribers()
            ->join('locations', 'users.location_id', '=', 'locations.id')
            ->join('provinces', 'locations.province_id', '=', 'provinces.id')
            ->selectRaw('provinces.name, COUNT(*) as count')
            ->groupBy('provinces.name')
            ->pluck('count', 'name')
            ->toArray();
    }

    private function getTopSymptoms(?int $locationId): array
    {
        return SymptomReport::when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->flatMap(fn($r) => $r->symptoms)
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    private function expectedCasesByType(int $locationId): array
    {
        $scores = RiskScore::where('location_id', $locationId)->latestPerLocation()->with('riskType:id,code')->get();
        $families = User::activeSubscribers()->where('location_id', $locationId)->count();

        return $scores->mapWithKeys(fn($s) => [
            ($s->riskType?->code ?? 'unknown') => [
                'score'    => $s->score,
                'level'    => $s->risk_level,
                'est_cases'=> round($families * ($s->score / 100) * 0.15), // 15% incidence estimate
            ]
        ])->toArray();
    }

    private function familiesReachedLast30Days(int $locationId): int
    {
        return DB::table('alert_deliveries')
            ->join('users', 'alert_deliveries.user_id', '=', 'users.id')
            ->where('users.location_id', $locationId)
            ->where('alert_deliveries.created_at', '>=', now()->subDays(30))
            ->where('alert_deliveries.status', 'delivered')
            ->distinct('alert_deliveries.user_id')
            ->count();
    }

    private function symptomHeatmap(int $locationId): array
    {
        return SymptomReport::where('location_id', $locationId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
