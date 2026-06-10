<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;

class GuardianPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view guardians');
    }

    public function view(User $user, Guardian $guardian): bool
    {
        return $user->hasPermissionTo('view guardians');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create guardians');
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $user->hasPermissionTo('edit guardians');
    }

    public function delete(User $user, Guardian $guardian): bool
    {
        return $user->hasPermissionTo('delete guardians');
    }

    public function restore(User $user, Guardian $guardian): bool
    {
        return $user->hasPermissionTo('delete guardians');
    }

    public function forceDelete(User $user, Guardian $guardian): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete guardians');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('delete guardians');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
