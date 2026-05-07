<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CreateAlertRequest;
use App\Models\Alert;
use App\Models\RiskScore;
use App\Models\RiskType;
use App\Services\AlertDispatchService;
use App\Services\MessageGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Alerts
 * Create and manage climate health alerts dispatched via SMS and WhatsApp.
 */
class AlertController extends BaseController
{
    public function __construct(
        private AlertDispatchService   $dispatcher,
        private MessageGeneratorService $generator,
    ) {}

    /**
     * List alerts
     *
     * Returns paginated alerts with delivery stats.
     * @authenticated
     * @queryParam location_id integer Filter by location. Example: 1
     * @queryParam status string Filter by status (pending|sent|failed). Example: sent
     * @queryParam risk_level string Filter by level (medium|high|critical). Example: high
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Alert::with(['location:id,province_id,district_id', 'location.province:id,name', 'location.district:id,name', 'riskType:id,code,name', 'creator:id,name'])
            ->when(!$user->canAccessAllZones() && $user->location_id,
                fn($q) => $q->where('location_id', $user->location_id))
            ->when($request->location_id, fn($q) => $q->where('location_id', $request->location_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->when($request->risk_level,  fn($q) => $q->where('risk_level', $request->risk_level))
            ->latest();

        return $this->paginated($query->paginate(20));
    }

    /**
     * Create and dispatch alert
     *
     * Creates an alert for a zone and queues it for dispatch.
     * @authenticated
     * @bodyParam location_id integer required Location to alert. Example: 1
     * @bodyParam risk_type string required (heat|malaria|diarrhea|respiratory). Example: malaria
     * @bodyParam channel string required (sms|whatsapp|both). Example: sms
     * @bodyParam scheduled_at string nullable ISO8601 datetime. Example: 2025-11-01T08:00:00Z
     */
    public function store(CreateAlertRequest $request): JsonResponse
    {
        $this->authorize('create', Alert::class);

        $riskType = RiskType::query()->where('code', $request->risk_type)->firstOrFail();
        $riskScore = RiskScore::where('location_id', $request->location_id)
            ->where('risk_type_id', $riskType->id)
            ->latestPerLocation()
            ->firstOrFail();

        // Generate messages
        $messages = $this->generator->generateForAlert($riskScore, $riskScore->location);

        $alert = Alert::create([
            'location_id'    => $request->location_id,
            'risk_score_id'  => $riskScore->id,
            'risk_type_id'   => $riskType->id,
            'risk_level'     => $riskScore->risk_level,
            'channel'        => $request->channel,
            'scheduled_at'   => $request->scheduled_at,
            'status'         => $request->scheduled_at ? 'pending' : 'pending',
            'created_by'     => $request->user()->id,
            ...$messages,
        ]);

        // If no schedule, dispatch immediately
        if (!$request->scheduled_at) {
            $this->dispatcher->dispatch($alert);
        }

        return $this->success($alert->load('location'), 'Alert created', 201);
    }

    /**
     * Get alert details with delivery report
     * @urlParam id integer required Alert ID. Example: 1
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $alert = Alert::with(['location', 'deliveries' => fn($q) => $q->limit(100)])->findOrFail($id);
        return $this->success($alert);
    }

    /**
     * Cancel a pending alert
     * @urlParam id integer required Alert ID. Example: 1
     * @authenticated
     */
    public function cancel(int $id): JsonResponse
    {
        $alert = Alert::where('status', 'pending')->findOrFail($id);
        $this->authorize('update', $alert);
        $alert->update(['status' => 'cancelled']);
        return $this->success(null, 'Alert cancelled');
    }

    /**
     * Handle Africa's Talking SMS delivery callback
     * Used by AT webhook — no authentication required.
     */
    public function deliveryCallback(Request $request): JsonResponse
    {
        $this->dispatcher->handleSmsCallback(
            $request->input('id'),
            $request->input('status'),
        );
        return response()->json(['status' => 'ok']);
    }
}
