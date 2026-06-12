<?php

namespace App\Models;

use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'is_ampa_member', 'ampa_member_since', 'notes'];

    protected function casts(): array
    {
        return [
            'is_ampa_member' => 'boolean',
            'ampa_member_since' => 'date',
        ];
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
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
