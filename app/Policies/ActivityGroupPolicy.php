<?php

namespace App\Policies;

use App\Models\ActivityGroup;
use App\Models\User;

class ActivityGroupPolicy
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
        return $user->hasPermissionTo('view extracurricular activities');
    }

    public function view(User $user, ActivityGroup $group): bool
    {
        return $user->hasPermissionTo('view extracurricular activities');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function update(User $user, ActivityGroup $group): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function delete(User $user, ActivityGroup $group): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function restore(User $user, ActivityGroup $group): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function forceDelete(User $user, ActivityGroup $group): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
