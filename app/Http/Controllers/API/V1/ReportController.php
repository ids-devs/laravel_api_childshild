<?php

namespace App\Http\Controllers\API\V1;

use App\Models\GeneratedReport;
use App\Models\Location;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    public function __construct(private DashboardAnalyticsService $analytics) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $reports = GeneratedReport::query()
            ->when(!$user->canAccessAllZones(), fn($q) => $q->where('location_id', $user->location_id))
            ->when($request->filled('province'), fn($q) => $q->where('province', $request->province))
            ->when($request->filled('district'), fn($q) => $q->where('district', $request->district))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest('created_at')
            ->paginate((int) $request->input('per_page', 10));

        return $this->paginated($reports);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $report = GeneratedReport::query()->findOrFail($id);

        if (!$user->canAccessAllZones() && $report->location_id !== $user->location_id) {
            abort(403, 'Access restricted to your zone');
        }

        return $this->success($report);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validate([
            'province' => ['required', 'string'],
            'district' => ['nullable', 'string'],
            'report_type' => ['required', 'in:weekly,monthly,custom'],
            'format' => ['required', 'in:pdf,excel'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $locationId = $user->location_id;
        if ($user->canAccessAllZones()) {
            $locationId = $request->input('location_id');
        }

        if ($locationId && !Location::query()->whereKey($locationId)->exists()) {
            return $this->error('Invalid location_id', 422);
        }

        $overview = $this->analytics->getOverview($locationId ? (int) $locationId : null);
        $label = match ($payload['report_type']) {
            'weekly' => 'Semanal',
            'monthly' => 'Mensal',
            default => 'Personalizado',
        };

        $report = GeneratedReport::query()->create([
            'name' => "Relatorio {$label} — {$payload['province']}",
            'period_start' => $payload['period_start'],
            'period_end' => $payload['period_end'],
            'province' => $payload['province'],
            'district' => $payload['district'] ?? null,
            'report_type' => $payload['report_type'],
            'format' => $payload['format'],
            'status' => 'ready',
            'download_url' => null,
            'alerts_count' => (int) ($overview['alerts_sent_today'] ?? 0),
            'families_covered' => (int) ($overview['total_families'] ?? 0),
            'location_id' => $locationId,
            'created_by' => $user->id,
        ]);

        return $this->success($report, 'Report generated successfully', 201);
    }
}
