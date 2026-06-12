<?php

namespace App\Policies;

use App\Models\ConsentType;
use App\Models\User;

class ConsentTypePolicy
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
        return $user->hasPermissionTo('view consents');
    }

    public function view(User $user, ConsentType $type): bool
    {
        return $user->hasPermissionTo('view consents');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function update(User $user, ConsentType $type): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function delete(User $user, ConsentType $type): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function restore(User $user, ConsentType $type): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function forceDelete(User $user, ConsentType $type): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
