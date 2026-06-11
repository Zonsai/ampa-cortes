<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use Database\Factories\ExtracurricularActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExtracurricularActivity extends Model
{
    /** @use HasFactory<ExtracurricularActivityFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'name',
        'slug',
        'short_description',
        'long_description',
        'status',
        'is_visible_for_families',
        'requires_ampa_membership',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'is_visible_for_families' => 'boolean',
            'requires_ampa_membership' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            $activity->slug = $activity->generateUniqueSlug();
        });

        static::updating(function (self $activity) {
            if ($activity->isDirty('name')) {
                $activity->slug = $activity->generateUniqueSlug();
            }
        });
    }

    private function generateUniqueSlug(): string
    {
        $base = Str::slug($this->name);
        $slug = $base;
        $counter = 1;

        while (
            self::withoutGlobalScopes()
                ->where('academic_year_id', $this->academic_year_id)
                ->where('slug', $slug)
                ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
                ->exists()
        ) {
            $counter++;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function activityGroups()
    {
        return $this->hasMany(ActivityGroup::class, 'activity_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'activity_id');
    }
}
