<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id', 'first_name', 'last_name', 'birth_date', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function guardians()
    {
        return $this->hasManyThrough(Guardian::class, Family::class, 'id', 'family_id', 'family_id', 'id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'student_classroom')
            ->withPivot('enrolled_at');
    }

    public function currentClassroom()
    {
        return $this->classrooms()
            ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
            ->first();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function formResponses()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function consentResponses()
    {
        return $this->hasMany(ConsentResponse::class);
    }
}
