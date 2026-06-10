<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyFactory> */
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
}
