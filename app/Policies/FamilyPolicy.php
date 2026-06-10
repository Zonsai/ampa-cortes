<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view families');
    }

    public function view(User $user, Family $family): bool
    {
        return $user->hasPermissionTo('view families');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create families');
    }

    public function update(User $user, Family $family): bool
    {
        return $user->hasPermissionTo('edit families');
    }

    public function delete(User $user, Family $family): bool
    {
        return $user->hasPermissionTo('delete families');
    }

    public function restore(User $user, Family $family): bool
    {
        return $user->hasPermissionTo('edit families');
    }

    public function forceDelete(User $user, Family $family): bool
    {
        return $user->hasRole('junta_ampa');
    }
}
