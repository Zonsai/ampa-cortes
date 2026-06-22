<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'status',
        'audience',
        'is_pinned',
        'published_at',
        'expires_at',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnnouncementStatus::class,
            'audience' => AnnouncementAudience::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Published and currently within its visibility window
     * (published_at in the past, expires_at null or in the future).
     */
    public function scopePublished(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('status', AnnouncementStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now));
    }

    /** Published announcements visible on the public website. */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()->whereIn('audience', AnnouncementAudience::publicValues());
    }

    /** Published announcements visible in the family zone. */
    public function scopeForFamilies(Builder $query): Builder
    {
        return $query->published()->whereIn('audience', AnnouncementAudience::familyValues());
    }

    /** Pinned first, then most recently published. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}
