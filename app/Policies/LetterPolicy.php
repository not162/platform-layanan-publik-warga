<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('letter.read')
            || $user->hasPermission('letter.verify')
            || $user->hasPermission('letter.approve')
            || $user->isWarga();
    }

    /**
     * Determine whether the user can view the model.
     * Warga can only view their own letter.
     */
    public function view(User $user, Letter $letter): bool
    {
        if ($user->isSuperadmin()
            || $user->hasPermission('letter.read')
            || $user->hasPermission('letter.verify')
            || $user->hasPermission('letter.approve')) {
            return true;
        }

        return $user->citizen && (int) $letter->citizen_id === (int) $user->citizen->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isWarga() || $user->citizen !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Letter $letter): bool
    {
        if ($letter->status === 'completed') {
            return false;
        }

        if ($user->isSuperadmin()
            || $user->hasPermission('letter.verify')
            || $user->hasPermission('letter.approve')) {
            return true;
        }

        return $user->citizen && (int) $letter->citizen_id === (int) $user->citizen->id && $letter->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     * Only draft letters can be deleted by their owner or superadmin.
     */
    public function delete(User $user, Letter $letter): bool
    {
        if ($letter->status !== 'draft') {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->citizen && (int) $letter->citizen_id === (int) $user->citizen->id;
    }

    /**
     * Determine whether the user can verify the letter.
     */
    public function verify(User $user, Letter $letter): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('letter.verify');
    }

    /**
     * Determine whether the user can approve the letter.
     */
    public function approve(User $user, Letter $letter): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('letter.approve');
    }

    /**
     * Determine whether the user can reject the letter.
     */
    public function reject(User $user, Letter $letter): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('letter.reject')
            || $user->hasPermission('letter.verify')
            || $user->hasPermission('letter.approve');
    }

    /**
     * Determine whether the user can complete the letter.
     */
    public function complete(User $user, Letter $letter): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('letter.complete')
            || $user->hasPermission('letter.approve');
    }
}
