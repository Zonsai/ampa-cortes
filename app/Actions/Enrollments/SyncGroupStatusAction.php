<?php

namespace App\Actions\Enrollments;

use App\Enums\ActivityGroupStatus;
use App\Models\ActivityGroup;

class SyncGroupStatusAction
{
    public function execute(ActivityGroup $group): void
    {
        if (! $group->status->isAutoManaged()) {
            return;
        }

        $newStatus = $group->hasAvailableSpots()
            ? ActivityGroupStatus::Open
            : ActivityGroupStatus::Full;

        if ($group->status !== $newStatus) {
            $group->update(['status' => $newStatus]);
        }
    }
}
