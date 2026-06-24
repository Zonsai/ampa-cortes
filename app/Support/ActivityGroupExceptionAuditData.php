<?php

namespace App\Support;

use App\Models\ActivityGroupException;

/**
 * Builds consistent audit-log payloads (label + properties) for activity group exception actions.
 */
class ActivityGroupExceptionAuditData
{
    public static function label(ActivityGroupException $exception): string
    {
        $exception->loadMissing('activityGroup.activity');

        $group = $exception->activityGroup;
        $activity = $group?->activity;

        return ($activity?->name ?? 'Actividad').' — '.($group?->name ?? 'Grupo');
    }

    /**
     * @return array<string, mixed>
     */
    public static function properties(ActivityGroupException $exception): array
    {
        $exception->loadMissing('activityGroup.activity');

        return [
            'activity_group_id' => $exception->activity_group_id,
            'activity' => $exception->activityGroup?->activity?->name,
            'group' => $exception->activityGroup?->name,
            'type' => $exception->type?->value,
            'original_date' => $exception->original_date?->toDateString(),
            'new_date' => $exception->new_date?->toDateString(),
            'new_starts_at' => $exception->new_starts_at,
            'new_ends_at' => $exception->new_ends_at,
            'new_location' => $exception->new_location,
            'reason' => $exception->reason,
        ];
    }
}
