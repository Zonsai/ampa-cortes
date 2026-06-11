<?php

namespace App\Policies;

use App\Models\ExtracurricularActivity;
use App\Models\User;

class ExtracurricularActivityPolicy
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

    public function view(User $user, ExtracurricularActivity $activity): bool
    {
        return $user->hasPermissionTo('view extracurricular activities');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function update(User $user, ExtracurricularActivity $activity): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function delete(User $user, ExtracurricularActivity $activity): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function restore(User $user, ExtracurricularActivity $activity): bool
    {
        return $user->hasPermissionTo('manage extracurricular activities');
    }

    public function forceDelete(User $user, ExtracurricularActivity $activity): bool
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
