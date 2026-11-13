<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
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
        return $user->hasRole('junta_ampa');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('junta_ampa') && ! $target->hasRole('super_admin');
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }
}
