<?php

namespace App\Models;

use Database\Factories\ConsentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentVersion extends Model
{
    /** @use HasFactory<ConsentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'consent_type_id',
        'version_number',
        'legal_text',
        'summary',
        'effective_from',
        'published_at',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ConsentResponse::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ConsentHistory::class);
    }
}
