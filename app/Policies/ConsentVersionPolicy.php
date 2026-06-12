<?php

namespace App\Policies;

use App\Models\ConsentVersion;
use App\Models\User;

class ConsentVersionPolicy
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

    public function view(User $user, ConsentVersion $version): bool
    {
        return $user->hasPermissionTo('view consents');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage consents');
    }

    public function update(User $user, ConsentVersion $version): bool
    {
        return false;
    }

    public function delete(User $user, ConsentVersion $version): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
