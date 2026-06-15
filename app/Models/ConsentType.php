<?php

namespace App\Models;

use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use Database\Factories\ConsentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentType extends Model
{
    /** @use HasFactory<ConsentTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'purpose',
        'scope',
        'is_rejectable',
        'is_revocable',
        'requires_image_review',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ConsentScope::class,
            'is_rejectable' => 'boolean',
            'is_revocable' => 'boolean',
            'requires_image_review' => 'boolean',
            'status' => ConsentTypeStatus::class,
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ConsentVersion::class);
    }

    /** Returns the latest published version for this consent type. */
    public function currentPublishedVersion(): HasOne
    {
        return $this->hasOne(ConsentVersion::class)
            ->whereNotNull('published_at')
            ->latestOfMany('version_number');
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
