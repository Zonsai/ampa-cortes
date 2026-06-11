<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
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
        return $user->hasPermissionTo('view enrollments');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('view enrollments');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage enrollments');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('manage enrollments');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('delete enrollments');
    }

    public function restore(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('manage enrollments');
    }

    public function forceDelete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete enrollments');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage enrollments');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
