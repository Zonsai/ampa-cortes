<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
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

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasRole(['junta_ampa', 'admin_extraescolares']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        if ($academicYear->classrooms()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('manage settings');
    }

    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function deleteAny(User $user): bool
    {
        // Bulk delete is too risky for academic years (cascades to classrooms and enrollments).
        // super_admin bypasses this via before().
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
