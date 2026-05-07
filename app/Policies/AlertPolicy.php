<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\ClinicUser;
use Illuminate\Auth\Access\Response;

/**
 * RBAC Policy for Alerts.
 *
 * Roles and their permissions:
 *  super-admin : all
 *  admin       : create, view, cancel
 *  government  : view only
 *  unicef      : view only
 *  ong         : create for own zone, view for own zone
 *  clinic      : create for own zone, view for own zone
 */
class AlertPolicy
{
    public function viewAny(ClinicUser $user): bool
    {
        return $user->hasAnyPermission(['view-alerts', 'create-alerts']);
    }

    public function view(ClinicUser $user, Alert $alert): bool
    {
        if ($user->canAccessAllZones()) return true;
        return $user->location_id === $alert->location_id;
    }

    public function create(ClinicUser $user): bool
    {
        return $user->hasPermissionTo('create-alerts');
    }

    public function update(ClinicUser $user, Alert $alert): bool
    {
        if ($user->isAdmin()) return true;
        return $user->location_id === $alert->location_id
            && $user->hasPermissionTo('create-alerts');
    }

    public function delete(ClinicUser $user, Alert $alert): bool
    {
        return $user->isAdmin();
    }
}
