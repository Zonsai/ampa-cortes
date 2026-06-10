<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
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
        return $user->hasRole(['junta_ampa', 'admin_extraescolares']);
    }

    public function view(User $user, Grade $grade): bool
    {
        return $user->hasRole(['junta_ampa', 'admin_extraescolares']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function update(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function restore(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function forceDelete(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('manage settings');
    }
}
