<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('complaint.read')
            || $user->hasPermission('complaint.manage')
            || $user->isWarga();
    }

    /**
     * Determine whether the user can view the model.
     * Warga can only view their own complaint. Staff can view all.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperadmin()
            || $user->hasPermission('complaint.read')
            || $user->hasPermission('complaint.manage')) {
            return true;
        }

        return (int) $complaint->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isWarga();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('complaint.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->isSuperadmin();
    }
}
