<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    /** @use HasFactory<\Database\Factories\GuardianFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id', 'user_id',
        'first_name', 'last_name', 'email', 'phone', 'phone_alt',
        'relationship', 'notes',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, Family::class, 'id', 'family_id', 'family_id', 'id');
    }
}
