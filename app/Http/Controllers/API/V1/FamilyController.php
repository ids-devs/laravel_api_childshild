<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Household;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Household::query()
            ->with([
                'user:id,location_id,channel,language,subscription_active,consent_status,created_at,phone_number_encrypted',
                'user.location:id,province_id,district_id,locality,latitude,longitude',
                'user.location.province:id,name',
                'user.location.district:id,name',
            ])
            ->when(!$user->canAccessAllZones(), fn($q) => $q->whereHas('user', fn($uq) => $uq->where('location_id', $user->location_id)))
            ->when($request->filled('province'), fn($q) => $q->whereHas('user.location.province', fn($pq) => $pq->where('name', $request->province)))
            ->when($request->filled('district'), fn($q) => $q->whereHas('user.location.district', fn($dq) => $dq->where('name', $request->district)))
            ->when($request->filled('locality'), fn($q) => $q->whereHas('user.location', fn($lq) => $lq->where('locality', $request->locality)))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->whereHas('user', fn($uq) => $uq->where('subscription_active', true));
                } elseif ($request->status === 'inactive') {
                    $q->whereHas('user', fn($uq) => $uq->where('subscription_active', false));
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = mb_strtolower((string) $request->search);
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->whereRaw('LOWER(phone_hash) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderByDesc('vulnerability_score');

        $families = $query->paginate((int) $request->input('per_page', 10));

        $data = collect($families->items())->map(fn(Household $household) => $this->transformHousehold($household))->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $families->currentPage(),
                'last_page' => $families->lastPage(),
                'per_page' => $families->perPage(),
                'total' => $families->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();
        $household = Household::query()
            ->with([
                'user:id,location_id,channel,language,subscription_active,consent_status,created_at,phone_number_encrypted',
                'user.location:id,province_id,district_id,locality,latitude,longitude',
                'user.location.province:id,name',
                'user.location.district:id,name',
            ])
            ->findOrFail($id);

        if (!$authUser->canAccessAllZones() && $household->user?->location_id !== $authUser->location_id) {
            abort(403, 'Access restricted to your zone');
        }

        return $this->success($this->transformHousehold($household));
    }

    public function stats(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $query = Household::query()->when(
            !$authUser->canAccessAllZones(),
            fn($q) => $q->whereHas('user', fn($uq) => $uq->where('location_id', $authUser->location_id))
        );

        $total = (clone $query)->count();
        $active = (clone $query)->whereHas('user', fn($uq) => $uq->where('subscription_active', true))->count();
        $highVulnerability = (clone $query)->where('vulnerability_score', '>=', 70)->count();
        $withPregnant = (clone $query)->where('pregnant_woman', true)->count();

        return $this->success([
            'total' => $total,
            'active' => $active,
            'high_vulnerability' => $highVulnerability,
            'with_pregnant' => $withPregnant,
        ]);
    }

    private function transformHousehold(Household $household): array
    {
        $user = $household->user;
        $location = $user?->location;

        return [
            'id' => $household->id,
            'phone_number' => $user ? $user->phone_number : null,
            'language' => $user?->language,
            'channel' => $user?->channel,
            'subscription_active' => (bool) $user?->subscription_active,
            'location' => $location ? [
                'id' => $location->id,
                'province' => $location->province?->name,
                'district' => $location->district?->name,
                'locality' => $location->locality,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'malaria_risk_static' => $location->malaria_risk_static,
                'sanitation_score' => $location->sanitation_score,
                'flood_risk' => $location->flood_risk,
                'is_coastal' => $location->is_coastal,
                'is_urban' => $location->is_urban,
            ] : null,
            'number_of_children' => $household->number_of_children,
            'children_age_groups' => $household->children_age_groups ?? [],
            'pregnant_woman' => (bool) $household->pregnant_woman,
            'weeks_pregnant' => $household->weeks_pregnant,
            'vulnerability_score' => (float) $household->vulnerability_score,
            'consent_status' => $user?->consent_status ? 'accepted' : 'declined',
            'created_at' => optional($user?->created_at)?->toIso8601String(),
        ];
    }
}
