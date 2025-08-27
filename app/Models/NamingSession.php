<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string|null $business_description
 * @property string $generation_mode
 * @property bool $deep_thinking
 * @property bool $is_starred
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_accessed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SessionResult> $latestResult
 * @property-read int|null $latest_result_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SessionResult> $results
 * @property-read int|null $results_count
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|NamingSession active()
 * @method static \Database\Factories\NamingSessionFactory factory($count = null, $state = [])
 * @method static Builder<static>|NamingSession newModelQuery()
 * @method static Builder<static>|NamingSession newQuery()
 * @method static Builder<static>|NamingSession query()
 * @method static Builder<static>|NamingSession recent()
 * @method static Builder<static>|NamingSession starred()
 * @method static Builder<static>|NamingSession whereBusinessDescription($value)
 * @method static Builder<static>|NamingSession whereCreatedAt($value)
 * @method static Builder<static>|NamingSession whereDeepThinking($value)
 * @method static Builder<static>|NamingSession whereGenerationMode($value)
 * @method static Builder<static>|NamingSession whereId($value)
 * @method static Builder<static>|NamingSession whereIsActive($value)
 * @method static Builder<static>|NamingSession whereIsStarred($value)
 * @method static Builder<static>|NamingSession whereLastAccessedAt($value)
 * @method static Builder<static>|NamingSession whereTitle($value)
 * @method static Builder<static>|NamingSession whereUpdatedAt($value)
 * @method static Builder<static>|NamingSession whereUserId($value)
 *
 * @mixin \Eloquent
 */
class NamingSession extends Model
{
    /** @use HasFactory<\Database\Factories\NamingSessionFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'business_description',
        'generation_mode',
        'deep_thinking',
        'is_starred',
        'is_active',
        'last_accessed_at',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'generation_mode' => 'creative',
        'deep_thinking' => false,
        'is_starred' => false,
        'is_active' => true,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $session): void {
            // Auto-generate title from business description if not provided
            if (empty($session->title) && ! empty($session->business_description)) {
                $session->title = Str::limit($session->business_description, 50, '...');
            } elseif (empty($session->title)) {
                $session->title = 'New Session '.now()->format('M j, g:i A');
            }
        });
    }

    /**
     * Get the user that owns the session.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the results for the session.
     *
     * @return HasMany<SessionResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(SessionResult::class, 'session_id');
    }

    /**
     * Get the latest result for the session.
     *
     * @return HasMany<SessionResult, $this>
     */
    public function latestResult(): HasMany
    {
        return $this->results()->orderBy('generation_timestamp', 'desc')->limit(1);
    }

    /**
     * Scope a query to only include starred sessions.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
    }

    /**
     * Scope a query to order by most recent.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to only include active sessions.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Mark the session as accessed.
     */
    public function markAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Toggle the starred status.
     */
    public function toggleStarred(): void
    {
        $this->update(['is_starred' => ! $this->is_starred]);
    }

    /**
     * Get preview text for the session.
     */
    public function getPreviewText(): string
    {
        if (empty($this->business_description)) {
            return 'No description provided';
        }

        return Str::limit($this->business_description, 77, '...');
    }

    /**
     * Get the session's formatted date group (Today, Yesterday, etc).
     */
    public function getDateGroup(): string
    {
        $date = $this->created_at;
        $now = now();

        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        if ($date->greaterThan($now->subDays(7))) {
            return 'Previous 7 Days';
        }

        if ($date->greaterThan($now->subDays(30))) {
            return 'Previous 30 Days';
        }

        return $date->format('F Y');
    }

    /**
     * Duplicate the session with its results.
     */
    public function duplicate(): self
    {
        $newSession = $this->replicate();
        $newSession->title = 'Copy of '.$this->title;
        $newSession->is_starred = false;
        $newSession->last_accessed_at = null;
        $newSession->save();

        // Copy the latest result if exists
        $latestResult = $this->latestResult()->first();
        if ($latestResult) {
            $newResult = $latestResult->replicate();
            $newResult->session_id = $newSession->id;
            $newResult->save();
        }

        return $newSession;
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'deep_thinking' => 'boolean',
            'is_starred' => 'boolean',
            'is_active' => 'boolean',
            'last_accessed_at' => 'datetime',
        ];
    }
}
