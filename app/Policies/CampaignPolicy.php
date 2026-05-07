<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\ClinicUser;

class CampaignPolicy
{
    public function viewAny(ClinicUser $user): bool
    {
        return $user->hasAnyPermission(['view-campaigns', 'create-campaigns']);
    }

    public function view(ClinicUser $user, Campaign $campaign): bool
    {
        if ($user->canAccessAllZones()) return true;
        return $campaign->created_by === $user->id;
    }

    public function create(ClinicUser $user): bool
    {
        return $user->hasPermissionTo('create-campaigns');
    }

    public function update(ClinicUser $user, Campaign $campaign): bool
    {
        if ($user->isAdmin()) return true;
        return $campaign->created_by === $user->id;
    }

    public function delete(ClinicUser $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }
}
