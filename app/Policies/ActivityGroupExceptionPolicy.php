<?php

namespace App\Policies;

use App\Models\ActivityGroupException;
use App\Models\User;

class ActivityGroupExceptionPolicy
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

    public function view(User $user, ActivityGroupException $exception): bool
    {
        return $user->hasPermissionTo('view extracurricular activities');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function update(User $user, ActivityGroupException $exception): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function delete(User $user, ActivityGroupException $exception): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function restore(User $user, ActivityGroupException $exception): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function forceDelete(User $user, ActivityGroupException $exception): bool
    {
        return false;
    }
}
