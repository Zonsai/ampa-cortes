<?php

namespace App\Models;

use Database\Factories\FormResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormResponse extends Model
{
    /** @use HasFactory<FormResponseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_id',
        'family_id',
        'student_id',
        'response_key',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormResponseAnswer::class);
    }

    /** Generates the response_key for a given family and optional student. */
    public static function buildResponseKey(Family $family, ?Student $student = null): string
    {
        return $student !== null
            ? "student:{$student->id}"
            : "family:{$family->id}";
    }
}
