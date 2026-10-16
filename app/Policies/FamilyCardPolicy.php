<?php

namespace App\Policies;

use App\Models\FamilyCard;
use App\Models\User;

class FamilyCardPolicy
{
    /**
     * Determine whether the user can view any family cards.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin()
            || $user->hasPermission('citizen.read')
            || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can view the family card.
     */
    public function view(User $user, FamilyCard $familyCard): bool
    {
        if ($user->isSuperadmin() || $user->hasPermission('citizen.read') || $user->hasPermission('citizen.manage')) {
            return true;
        }

        return $user->citizen && (int) $user->citizen->family_card_id === (int) $familyCard->id;
    }

    /**
     * Determine whether the user can create family cards.
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can update the family card.
     */
    public function update(User $user, FamilyCard $familyCard): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('citizen.manage');
    }

    /**
     * Determine whether the user can delete the family card.
     */
    public function delete(User $user, FamilyCard $familyCard): bool
    {
        return $user->isSuperadmin() || $user->hasPermission('citizen.manage');
    }
}
