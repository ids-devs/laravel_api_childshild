<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CreateCampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Jobs\DispatchCampaignJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Campaigns
 * Create and manage SMS/WhatsApp broadcast campaigns.
 */
class CampaignController extends BaseController
{
    /**
     * List campaigns
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::with('creator:id,name')
            ->when(
                !$request->user()->canAccessAllZones(),
                fn($q) => $q->where('created_by', $request->user()->id)
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return $this->paginated($campaigns);
    }

    /**
     * Create campaign
     * @authenticated
     */
    public function store(CreateCampaignRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        // Count recipients
        $recipientCount = User::activeSubscribers()
            ->when($request->target_provinces, fn($q) =>
                $q->whereHas('location.province', fn($pq) => $pq->whereIn('name', $request->target_provinces)))
            ->when($request->target_districts, fn($q) =>
                $q->whereHas('location.district', fn($dq) => $dq->whereIn('name', $request->target_districts)))
            ->count();

        $campaign = Campaign::create([
            ...$request->validated(),
            'created_by'       => $request->user()->id,
            'recipients_total' => $recipientCount,
        ]);

        if ($campaign->status === 'draft' && !$request->scheduled_at) {
            // Immediately schedule
            $campaign->update(['status' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)]);
            DispatchCampaignJob::dispatch($campaign)->delay(now()->addMinutes(5));
        }

        return $this->success($campaign, 'Campaign created', 201);
    }

    /**
     * Get campaign with delivery stats
     * @urlParam id integer required Campaign ID. Example: 1
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::with('creator:id,name')->findOrFail($id);
        $this->authorize('view', $campaign);
        return $this->success($campaign);
    }

    /**
     * Cancel a scheduled campaign
     * @urlParam id integer required Campaign ID. Example: 1
     * @authenticated
     */
    public function cancel(int $id): JsonResponse
    {
        $campaign = Campaign::whereIn('status', ['draft', 'scheduled'])->findOrFail($id);
        $this->authorize('update', $campaign);
        $campaign->update(['status' => 'cancelled']);
        return $this->success(null, 'Campaign cancelled');
    }
}
