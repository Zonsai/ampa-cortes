<?php

namespace App\Policies;

use App\Models\ConsentResponse;
use App\Models\User;

class ConsentResponsePolicy
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

    public function view(User $user, ConsentResponse $response): bool
    {
        return $user->hasPermissionTo('view consents');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ConsentResponse $response): bool
    {
        return false;
    }

    public function delete(User $user, ConsentResponse $response): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
