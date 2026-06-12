<?php

namespace App\Policies;

use App\Models\FormResponse;
use App\Models\User;

class FormResponsePolicy
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
        return $user->hasPermissionTo('view forms');
    }

    public function view(User $user, FormResponse $formResponse): bool
    {
        return $user->hasPermissionTo('view forms');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, FormResponse $formResponse): bool
    {
        return false;
    }

    public function delete(User $user, FormResponse $formResponse): bool
    {
        return false;
    }

    public function restore(User $user, FormResponse $formResponse): bool
    {
        return false;
    }

    public function forceDelete(User $user, FormResponse $formResponse): bool
    {
        return false;
    }
}
