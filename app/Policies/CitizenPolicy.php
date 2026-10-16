<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\User;

class CitizenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('citizen.read')
            || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can view the model.
     * Warga A cannot view Warga B.
     */
    public function view(User $user, Citizen $citizen): bool
    {
        if ($user->isSuperadmin() || $user->hasPermission('citizen.read') || $user->hasPermission('citizen.manage')) {
            return true;
        }

        return (int) $citizen->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Citizen $citizen): bool
    {
        if ($user->isSuperadmin() || $user->hasPermission('citizen.manage')) {
            return true;
        }

        return (int) $citizen->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Citizen $citizen): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can verify pending citizen application.
     */
    public function verify(User $user, Citizen $citizen): bool
    {
        return $user->isSuperadmin()
            || $user->isSekretaris()
            || $user->isKetuaRt()
            || $user->hasPermission('citizen.manage');
    }
}
