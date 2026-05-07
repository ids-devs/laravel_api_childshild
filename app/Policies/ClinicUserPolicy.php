<?php

namespace App\Policies;

use App\Models\ClinicUser;

class ClinicUserPolicy
{
    public function viewAny(ClinicUser $user): bool
    {
        return $user->hasPermissionTo('manage-users');
    }

    public function create(ClinicUser $user): bool
    {
        return $user->hasPermissionTo('manage-users');
    }

    public function update(ClinicUser $user, ClinicUser $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function delete(ClinicUser $user, ClinicUser $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
