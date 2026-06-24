<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PriceType;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'family_id',
        'activity_id',
        'activity_group_id',
        'academic_year_id',
        'status',
        'registered_at',
        'enrolled_at',
        'ended_at',
        'amount',
        'price_type',
        'waitlist_position',
        'family_notes',
        'internal_notes',
        'paid_at',
        'payment_method',
        'attendance_from',
        'attendance_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'price_type' => PriceType::class,
            'registered_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'ended_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'attendance_from' => 'date',
            'attendance_until' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function activity()
    {
        return $this->belongsTo(ExtracurricularActivity::class, 'activity_id');
    }

    public function activityGroup()
    {
        return $this->belongsTo(ActivityGroup::class, 'activity_group_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
