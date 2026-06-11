<?php

namespace App\Models;

use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'title',
        'description',
        'status',
        'response_scope',
        'target_type',
        'allow_edit',
        'opens_at',
        'closes_at',
        'internal_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => FormStatus::class,
            'response_scope' => FormResponseScope::class,
            'target_type' => FormTargetType::class,
            'allow_edit' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function formTargetItems(): HasMany
    {
        return $this->hasMany(FormTargetItem::class);
    }

    public function formResponses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    /**
     * Returns true when the form is currently open for responses:
     * - status must be Published
     * - opens_at is null or in the past
     * - closes_at is null or in the future
     */
    public function isOpenNow(): bool
    {
        if ($this->status !== FormStatus::Published) {
            return false;
        }

        $now = now();

        if ($this->opens_at !== null && $this->opens_at->gt($now)) {
            return false;
        }

        if ($this->closes_at !== null && $this->closes_at->lte($now)) {
            return false;
        }

        return true;
    }
}
