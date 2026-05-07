<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds RBAC roles and permissions for the ChildShield dashboard.
 *
 * Roles (in descending privilege):
 *   super-admin  → full system access
 *   admin        → user management + all data
 *   government   → national read + export + user mgmt (own org)
 *   unicef        → national read + export + user mgmt (own org)
 *   ong          → zone read + create alerts/campaigns (own zone)
 *   clinic       → zone read + create alerts (own zone)
 */
class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Define permissions ──────────────────────────────────────────

        $permissions = [
            // Locations
            'view-locations',
            'manage-locations',

            // Climate data
            'view-climate-data',
            'manage-climate-data',

            // Risk scores
            'view-risk-scores',
            'manage-risk-engine',

            // Alerts
            'view-alerts',
            'create-alerts',
            'manage-alerts',        // cancel/delete any

            // Campaigns
            'view-campaigns',
            'create-campaigns',
            'manage-campaigns',     // cancel/delete any

            // Dashboard
            'view-dashboard',
            'view-national-dashboard',  // all zones
            'export-reports',

            // Symptoms
            'view-symptoms',
            'manage-symptoms',

            // Users
            'manage-users',

            // System
            'view-audit-logs',
            'manage-system-config',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // ─── Define roles and assign permissions ─────────────────────────

        // super-admin: all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::all());

        // admin: everything except system config
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo([
            'view-locations', 'manage-locations',
            'view-climate-data', 'manage-climate-data',
            'view-risk-scores', 'manage-risk-engine',
            'view-alerts', 'create-alerts', 'manage-alerts',
            'view-campaigns', 'create-campaigns', 'manage-campaigns',
            'view-dashboard', 'view-national-dashboard', 'export-reports',
            'view-symptoms', 'manage-symptoms',
            'manage-users',
            'view-audit-logs',
        ]);

        // government: national read + export + limited user mgmt
        $government = Role::firstOrCreate(['name' => 'government', 'guard_name' => 'api']);
        $government->givePermissionTo([
            'view-locations',
            'view-climate-data',
            'view-risk-scores',
            'view-alerts',
            'view-campaigns',
            'view-dashboard', 'view-national-dashboard', 'export-reports',
            'view-symptoms',
        ]);

        // unicef: same as government
        $unicef = Role::firstOrCreate(['name' => 'unicef', 'guard_name' => 'api']);
        $unicef->givePermissionTo([
            'view-locations',
            'view-climate-data',
            'view-risk-scores',
            'view-alerts', 'create-alerts',
            'view-campaigns', 'create-campaigns',
            'view-dashboard', 'view-national-dashboard', 'export-reports',
            'view-symptoms',
            'manage-users',     // manage users in own org
        ]);

        // ong: zone operations
        $ong = Role::firstOrCreate(['name' => 'ong', 'guard_name' => 'api']);
        $ong->givePermissionTo([
            'view-locations',
            'view-climate-data',
            'view-risk-scores',
            'view-alerts', 'create-alerts',
            'view-campaigns', 'create-campaigns',
            'view-dashboard', 'export-reports',
            'view-symptoms',
        ]);

        // clinic: zone read + create alerts
        $clinic = Role::firstOrCreate(['name' => 'clinic', 'guard_name' => 'api']);
        $clinic->givePermissionTo([
            'view-locations',
            'view-climate-data',
            'view-risk-scores',
            'view-alerts', 'create-alerts',
            'view-campaigns',
            'view-dashboard', 'export-reports',
            'view-symptoms',
        ]);

        $this->command->info('[✓] Roles and permissions seeded.');
    }
}
