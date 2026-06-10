<?php

namespace App\Policies;

use App\Models\SchoolStage;
use App\Models\User;

class SchoolStagePolicy
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

    public function view(User $user, SchoolStage $schoolStage): bool
    {
        return $user->hasRole(['junta_ampa', 'admin_extraescolares']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function update(User $user, SchoolStage $schoolStage): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function delete(User $user, SchoolStage $schoolStage): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function restore(User $user, SchoolStage $schoolStage): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function forceDelete(User $user, SchoolStage $schoolStage): bool
    {
        return $user->hasPermissionTo('manage settings');
    }

    public function deleteAny(User $user): bool
    {
        // Bulk delete cascades to grades and classrooms. super_admin bypasses via before().
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
