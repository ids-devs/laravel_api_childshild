<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ClinicUser;
use App\Models\User;
use App\Models\Location;
use App\Services\RiskEngineService;
use App\Services\ClimateDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @group Admin
 * Administration: users, roles, system config (super-admin only).
 */
class AdminController extends BaseController
{
    public function __construct(
        private RiskEngineService  $riskEngine,
        private ClimateDataService $climateService,
    ) {}

    /**
     * List all dashboard users
     * @authenticated
     * @middleware permission:manage-users
     */
    public function users(Request $request): JsonResponse
    {
        $users = ClinicUser::with('roles')
            ->when($request->organization_type, fn($q) => $q->byOrgType($request->organization_type))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20);

        return $this->paginated($users);
    }

    /**
     * Update user role
     * @urlParam id integer required User ID. Example: 1
     * @bodyParam role string required Role name. Example: clinic
     * @authenticated
     * @middleware permission:manage-users
     */
    public function updateUserRole(Request $request, int $id): JsonResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);

        $user = ClinicUser::findOrFail($id);
        $user->syncRoles([$request->role]);

        return $this->success(null, 'Role updated');
    }

    /**
     * Deactivate/activate a user
     * @urlParam id integer required User ID. Example: 1
     * @authenticated
     */
    public function toggleUserStatus(int $id): JsonResponse
    {
        $user = ClinicUser::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return $this->success(['is_active' => $user->is_active], 'Status updated');
    }

    /**
     * Trigger full risk recalculation across all locations
     * @authenticated
     * @middleware permission:manage-risk-engine
     */
    public function triggerRiskRecalculation(): JsonResponse
    {
        // Dispatch async job
        \App\Jobs\CalculateRiskScoresJob::dispatch();
        return $this->success(null, 'Risk recalculation queued');
    }

    /**
     * Trigger climate data refresh for all locations
     * @authenticated
     * @middleware permission:manage-climate-data
     */
    public function triggerClimateRefresh(): JsonResponse
    {
        \App\Jobs\FetchClimateDataJob::dispatch();
        return $this->success(null, 'Climate data refresh queued');
    }

    /**
     * Get system stats (families, alerts, jobs)
     * @authenticated
     */
    public function systemStats(): JsonResponse
    {
        return $this->success([
            'total_families'       => User::count(),
            'active_subscriptions' => User::activeSubscribers()->count(),
            'total_dashboard_users'=> ClinicUser::count(),
            'total_locations'      => Location::count(),
            'roles'                => Role::with('permissions')->get(),
        ]);
    }

    /**
     * List all roles and permissions
     * @authenticated
     */
    public function rolesPermissions(): JsonResponse
    {
        return $this->success([
            'roles'       => Role::with('permissions:name')->get(['id', 'name']),
            'permissions' => Permission::all(['id', 'name']),
        ]);
    }
}
