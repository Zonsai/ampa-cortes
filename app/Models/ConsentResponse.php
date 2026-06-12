<?php

namespace App\Models;

use App\Enums\ConsentResponseStatus;
use Database\Factories\ConsentResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentResponse extends Model
{
    /** @use HasFactory<ConsentResponseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consent_type_id',
        'consent_version_id',
        'family_id',
        'student_id',
        'subject_key',
        'status',
        'responded_at',
        'responded_by_id',
        'ip_address',
        'user_agent',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConsentResponseStatus::class,
            'responded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Builds the subject_key string for a given family/student pair.
     * per_family: "family:{family_id}", per_student: "student:{student_id}"
     */
    public static function buildSubjectKey(Family $family, ?Student $student): string
    {
        return $student !== null
            ? "student:{$student->id}"
            : "family:{$family->id}";
    }

    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    public function consentVersion(): BelongsTo
    {
        return $this->belongsTo(ConsentVersion::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ConsentHistory::class)->orderBy('created_at');
    }
}
