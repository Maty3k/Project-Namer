<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ShareAccess model for tracking share analytics.
 *
 * Records each access to a shared resource with IP address,
 * user agent, and referrer information for analytics purposes.
 *
 * @template TFactory of \Database\Factories\ShareAccessFactory
 *
 * @property int $id
 * @property int $share_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property \Illuminate\Support\Carbon $accessed_at
 * @property-read \App\Models\Share<\Database\Factories\ShareFactory> $share
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> betweenDates(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
 * @method static \Database\Factories\ShareAccessFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> fromIp(string $ipAddress)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereAccessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereReferrer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereShareId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\ShareAccess<TFactory> whereUserAgent($value)
 *
 * @mixin \Eloquent
 */
final class ShareAccess extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'share_id',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $shareAccess): void {
            if (empty($shareAccess->accessed_at)) {
                $shareAccess->accessed_at = now();
            }
        });
    }

    /**
     * Share that was accessed.
     *
     * @return BelongsTo<Share<\Database\Factories\ShareFactory>, $this>
     */
    public function share(): BelongsTo
    {
        /** @phpstan-ignore-next-line - Laravel relationship generic type resolution */
        return $this->belongsTo(Share::class);
    }

    /**
     * Scope to recent accesses within specified days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $days = 30): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by IP address.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>
     */
    public function scopeFromIp(\Illuminate\Database\Eloquent\Builder $query, string $ipAddress): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShareAccess<TFactory>>
     */
    public function scopeBetweenDates(\Illuminate\Database\Eloquent\Builder $query, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('accessed_at', [$startDate, $endDate]);
    }

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
            'share_id' => 'integer',
        ];
    }
}
