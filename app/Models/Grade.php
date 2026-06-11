<?php

namespace App\Models;

use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    protected $fillable = ['school_stage_id', 'name', 'sort_order'];

    public function schoolStage()
    {
        return $this->belongsTo(SchoolStage::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function activityGroups()
    {
        return $this->belongsToMany(ActivityGroup::class, 'activity_group_grade');
    }
}
