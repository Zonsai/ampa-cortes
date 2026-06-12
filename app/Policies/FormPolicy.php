<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
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

    public function view(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('view forms');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function update(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function restore(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function forceDelete(User $user, Form $form): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
