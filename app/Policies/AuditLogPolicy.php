<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
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
        return $user->hasPermissionTo('view audit logs');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('view audit logs');
    }

    // Audit logs are immutable and only created by the system.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
