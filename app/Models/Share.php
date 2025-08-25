<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Share model for managing shareable content.
 *
 * Provides functionality for creating public and password-protected shares
 * of name generation results with analytics tracking and expiration support.
 *
 * @template TFactory of \Database\Factories\ShareFactory
 *
 * @property int $id
 * @property string $uuid
 * @property string $shareable_type
 * @property int $shareable_id
 * @property int|null $user_id
 * @property string|null $title
 * @property string|null $description
 * @property string $share_type
 * @property string|null $password_hash
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $view_count
 * @property \Illuminate\Support\Carbon|null $last_viewed_at
 * @property bool $is_active
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShareAccess<\Database\Factories\ShareAccessFactory>> $accesses
 * @property-read int|null $accesses_count
 * @property-write string|null $password
 * @property-read \Illuminate\Database\Eloquent\Model $shareable
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> accessible()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> active()
 * @method static \Database\Factories\ShareFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereLastViewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> wherePasswordHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereShareType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereShareableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereShareableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Share<TFactory> whereViewCount($value)
 *
 * @mixin \Eloquent
 */
final class Share extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'shareable_type',
        'shareable_id',
        'user_id',
        'title',
        'description',
        'share_type',
        'password',
        'expires_at',
        'settings',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $share): void {
            if (empty($share->uuid)) {
                $share->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Set password attribute and hash it.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<null, ?string>
     */
    protected function password(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(set: fn (?string $value) => ['password_hash' => $value ? Hash::make($value) : null]);
    }

    /**
     * User who created the share.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to shareable models.
     *
     * @return MorphTo<Model, $this>
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Share access records for analytics.
     *
     * @return HasMany<ShareAccess<\Database\Factories\ShareAccessFactory>, $this>
     */
    public function accesses(): HasMany
    {
        /** @phpstan-ignore-next-line - Laravel relationship generic type resolution */
        return $this->hasMany(ShareAccess::class);
    }

    /**
     * Generate the public share URL.
     */
    public function getShareUrl(): string
    {
        return url("/share/{$this->uuid}");
    }

    /**
     * Check if the share is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the share is accessible (active and not expired).
     */
    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Record an access to this share.
     */
    public function recordAccess(?string $ipAddress = null, ?string $userAgent = null, ?string $referrer = null): void
    {
        $this->accesses()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
        ]);

        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Validate password for protected shares.
     */
    public function validatePassword(string $password): bool
    {
        if ($this->share_type === 'public') {
            return true;
        }

        return $this->password_hash && Hash::check($password, $this->password_hash);
    }

    /**
     * Scope to active shares only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Share<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Share<TFactory>>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to accessible shares (active and not expired).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Share<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Share<TFactory>>
     */
    public function scopeAccessible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->active();
    }

    /**
     * Scope by share type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Share<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Share<TFactory>>
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('share_type', $type);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'is_active' => 'boolean',
            'settings' => 'array',
            'view_count' => 'integer',
        ];
    }
}
