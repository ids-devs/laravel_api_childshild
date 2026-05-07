<?php

namespace App\Http\Controllers\API\V1;

use App\Models\SymptomReport;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Symptom Reports
 * Community-reported symptoms for epidemiological surveillance.
 */
class SymptomReportController extends BaseController
{
    /**
     * Submit a symptom report (from WhatsApp/USSD)
     *
     * Registers a family's observed symptoms. Does NOT diagnose — for surveillance only.
     *
     * @bodyParam phone_hash string required User phone hash. Example: abc123
     * @bodyParam symptoms array required List of symptoms. Example: ["fever","diarrhea"]
     * @bodyParam notes string nullable Additional observations. Example: Started yesterday
     * @bodyParam channel string required (whatsapp|ussd|dashboard). Example: whatsapp
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'phone_hash' => 'required|string|exists:users,phone_hash',
            'symptoms'   => 'required|array|min:1',
            'symptoms.*' => 'in:fever,diarrhea,cough,vomiting,rash,respiratory,malaria_symptoms,other',
            'notes'      => 'nullable|string|max:500',
            'channel'    => 'required|in:whatsapp,ussd,dashboard',
        ]);

        $user = User::where('phone_hash', $request->phone_hash)->firstOrFail();

        $report = SymptomReport::create([
            'user_id'     => $user->id,
            'location_id' => $user->location_id,
            'symptoms'    => $request->symptoms,
            'notes'       => $request->notes,
            'channel'     => $request->channel,
        ]);

        return $this->success($report, 'Report registered', 201);
    }

    /**
     * List symptom reports (dashboard)
     * @authenticated
     * @queryParam location_id integer Filter by location. Example: 1
     * @queryParam days integer Look-back window in days. Example: 30
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = min($request->days ?? 30, 90);

        $reports = SymptomReport::with(['location:id,province_id,district_id', 'location.province:id,name', 'location.district:id,name'])
            ->when(!$user->canAccessAllZones(), fn($q) => $q->where('location_id', $user->location_id))
            ->when($request->location_id, fn($q) => $q->where('location_id', $request->location_id))
            ->where('created_at', '>=', now()->subDays($days))
            ->latest()
            ->paginate(50);

        return $this->paginated($reports);
    }

    /**
     * Get symptom aggregation by type (surveillance data)
     * @authenticated
     */
    public function aggregated(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = min($request->days ?? 30, 90);

        $reports = SymptomReport::when(!$user->canAccessAllZones(), fn($q) => $q->where('location_id', $user->location_id))
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $aggregated = $reports->flatMap(fn($r) => $r->symptoms)->countBy()->sortDesc();

        return $this->success([
            'period_days' => $days,
            'total_reports' => $reports->count(),
            'by_symptom' => $aggregated,
        ]);
    }
}
