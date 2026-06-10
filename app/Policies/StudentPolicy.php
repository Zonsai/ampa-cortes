<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view students');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('view students');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create students');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('edit students');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('delete students');
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('delete students');
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->hasRole('junta_ampa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete students');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('delete students');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
