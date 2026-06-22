<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
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
        return $user->hasPermissionTo('view announcements');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('view announcements');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage announcements');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage announcements');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage announcements');
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage announcements');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage announcements');
    }
}
