<?php

namespace App\Policies;

use App\Models\FormField;
use App\Models\User;

class FormFieldPolicy
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

    public function view(User $user, FormField $formField): bool
    {
        return $user->hasPermissionTo('view forms');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function update(User $user, FormField $formField): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function delete(User $user, FormField $formField): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function restore(User $user, FormField $formField): bool
    {
        return $user->hasPermissionTo('manage forms');
    }

    public function forceDelete(User $user, FormField $formField): bool
    {
        return false;
    }
}
