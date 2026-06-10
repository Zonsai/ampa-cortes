<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolStage extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolStageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'sort_order'];

    public function grades()
    {
        return $this->hasMany(Grade::class)->orderBy('sort_order');
    }
}
