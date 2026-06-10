<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    /** @use HasFactory<\Database\Factories\ClassroomFactory> */
    use HasFactory;

    protected $fillable = ['academic_year_id', 'grade_id', 'name', 'tutor'];

    public function getFullNameAttribute(): string
    {
        return "{$this->grade?->name} {$this->name}";
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_classroom')
            ->withPivot('enrolled_at');
    }
}
