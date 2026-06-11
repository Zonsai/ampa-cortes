<?php

namespace App\Models;

use App\Enums\ActivityGroupStatus;
use App\Enums\EnrollmentStatus;
use Database\Factories\ActivityGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityGroup extends Model
{
    /** @use HasFactory<ActivityGroupFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activity_id',
        'name',
        'weekdays',
        'starts_at',
        'ends_at',
        'max_spots',
        'price_member',
        'price_non_member',
        'provider',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'status' => ActivityGroupStatus::class,
            'price_member' => 'decimal:2',
            'price_non_member' => 'decimal:2',
        ];
    }

    public function activity()
    {
        return $this->belongsTo(ExtracurricularActivity::class, 'activity_id');
    }

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'activity_group_grade');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Enrollments that occupy a physical spot (enrolled, pending_payment, paid). */
    public function occupyingEnrollments()
    {
        $occupyingStatuses = array_map(
            fn (EnrollmentStatus $s) => $s->value,
            array_filter(EnrollmentStatus::cases(), fn ($s) => $s->occupiesSpot())
        );

        return $this->hasMany(Enrollment::class)->whereIn('status', $occupyingStatuses);
    }

    /** Enrollments on the waitlist. */
    public function waitlistEnrollments()
    {
        return $this->hasMany(Enrollment::class)->where('status', EnrollmentStatus::Waitlist->value);
    }

    public function spotsOccupied(): int
    {
        return $this->occupyingEnrollments()->count();
    }

    public function availableSpots(): int
    {
        return max(0, $this->max_spots - $this->spotsOccupied());
    }

    public function hasAvailableSpots(): bool
    {
        return $this->availableSpots() > 0;
    }

    public function nextWaitlistPosition(): int
    {
        return (int) $this->waitlistEnrollments()->max('waitlist_position') + 1;
    }

    public function weekdaysLabel(): string
    {
        $labels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
        $days = $this->weekdays ?? [];
        sort($days);

        return implode(', ', array_map(fn ($d) => $labels[$d] ?? $d, $days));
    }
}
