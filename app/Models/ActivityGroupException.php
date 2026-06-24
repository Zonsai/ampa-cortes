<?php

namespace App\Models;

use App\Enums\ExceptionType;
use Database\Factories\ActivityGroupExceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityGroupException extends Model
{
    /** @use HasFactory<ActivityGroupExceptionFactory> */
    use HasFactory;

    protected $fillable = [
        'activity_group_id',
        'original_date',
        'type',
        'new_date',
        'new_starts_at',
        'new_ends_at',
        'new_location',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'original_date' => 'date',
            'new_date' => 'date',
            'type' => ExceptionType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $exception) {
            $exception->validateCoherence();
        });
    }

    public function activityGroup()
    {
        return $this->belongsTo(ActivityGroup::class);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateCoherence(): void
    {
        match ($this->type) {
            ExceptionType::Cancelled => $this->validateCancelled(),
            ExceptionType::Modified => $this->validateModified(),
            ExceptionType::Extra => $this->validateExtra(),
        };
    }

    private function validateCancelled(): void
    {
        if ($this->new_date || $this->new_starts_at || $this->new_ends_at || $this->new_location) {
            throw new \InvalidArgumentException('A cancelled exception must not have new date, time, or location.');
        }

        if (! $this->original_date) {
            throw new \InvalidArgumentException('A cancelled exception requires an original_date.');
        }

        $this->validateOriginalDateMatchesWeekday();
    }

    private function validateModified(): void
    {
        if (! $this->original_date) {
            throw new \InvalidArgumentException('A modified exception requires an original_date.');
        }

        if (! $this->new_date && ! $this->new_starts_at && ! $this->new_ends_at && ! $this->new_location) {
            throw new \InvalidArgumentException('A modified exception must change at least one field (date, time, or location).');
        }

        $this->validateOriginalDateMatchesWeekday();
    }

    private function validateExtra(): void
    {
        if ($this->original_date) {
            throw new \InvalidArgumentException('An extra exception must not have an original_date.');
        }

        if (! $this->new_date || ! $this->new_starts_at || ! $this->new_ends_at) {
            throw new \InvalidArgumentException('An extra exception requires new_date, new_starts_at, and new_ends_at.');
        }

        $this->validateExtraDoesNotDuplicateRegular();
        $this->validateExtraIsNotDuplicate();
    }

    private function validateOriginalDateMatchesWeekday(): void
    {
        $group = $this->activityGroup ?? ActivityGroup::find($this->activity_group_id);

        if (! $group) {
            return;
        }

        $weekdays = $group->weekdays ?? [];
        $dayOfWeek = $this->original_date->dayOfWeekIso;

        if (! in_array($dayOfWeek, $weekdays)) {
            throw new \InvalidArgumentException(
                "original_date ({$this->original_date->format('Y-m-d')}) does not fall on a recurring weekday of this group."
            );
        }
    }

    private function validateExtraIsNotDuplicate(): void
    {
        $query = self::where('activity_group_id', $this->activity_group_id)
            ->where('type', ExceptionType::Extra)
            ->whereDate('new_date', $this->new_date->format('Y-m-d'))
            ->where('new_starts_at', $this->new_starts_at);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException(
                'An extra session with the same date and start time already exists for this group.'
            );
        }
    }

    private function validateExtraDoesNotDuplicateRegular(): void
    {
        $group = $this->activityGroup ?? ActivityGroup::find($this->activity_group_id);

        if (! $group) {
            return;
        }

        $weekdays = $group->weekdays ?? [];
        $dayOfWeek = $this->new_date->dayOfWeekIso;

        if (in_array($dayOfWeek, $weekdays) && $this->new_starts_at === $group->starts_at) {
            throw new \InvalidArgumentException(
                'An extra session must not duplicate a regular session of the same group (same date and start time).'
            );
        }
    }
}
