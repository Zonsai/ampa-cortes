<?php

namespace App\Models;

use App\Enums\ConsentEventType;
use Database\Factories\ConsentHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentHistory extends Model
{
    /** @use HasFactory<ConsentHistoryFactory> */
    use HasFactory;

    // Append-only audit log — no updated_at column.
    public const UPDATED_AT = null;

    protected $fillable = [
        'consent_response_id',
        'consent_version_id',
        'consent_type_id',
        'family_id',
        'student_id',
        'event_type',
        'performed_by_id',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => ConsentEventType::class,
        ];
    }

    public function consentResponse(): BelongsTo
    {
        return $this->belongsTo(ConsentResponse::class);
    }

    public function consentVersion(): BelongsTo
    {
        return $this->belongsTo(ConsentVersion::class);
    }

    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
