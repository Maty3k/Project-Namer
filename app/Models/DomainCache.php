<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Domain availability cache model.
 *
 * Stores domain availability results to avoid repeated API calls
 * and improve application performance.
 *
 * @property int $id
 * @property string $domain
 * @property bool $available
 * @property \Illuminate\Support\Carbon $checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static>|DomainCache expired()
 * @method static Builder<static>|DomainCache fresh()
 * @method static Builder<static>|DomainCache newModelQuery()
 * @method static Builder<static>|DomainCache newQuery()
 * @method static Builder<static>|DomainCache query()
 * @method static Builder<static>|DomainCache whereAvailable($value)
 * @method static Builder<static>|DomainCache whereCheckedAt($value)
 * @method static Builder<static>|DomainCache whereCreatedAt($value)
 * @method static Builder<static>|DomainCache whereDomain($value)
 * @method static Builder<static>|DomainCache whereId($value)
 * @method static Builder<static>|DomainCache whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class DomainCache extends Model
{
    protected $table = 'domain_cache';

    protected $fillable = [
        'domain',
        'available',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * Scope to get fresh cache entries (not expired).
     *
     * @param  Builder<DomainCache>  $query
     * @return Builder<DomainCache>
     */
    public function scopeFresh(Builder $query): Builder
    {
        return $query->where('checked_at', '>=', now()->subHours(24));
    }

    /**
     * Scope to get expired cache entries.
     *
     * @param  Builder<DomainCache>  $query
     * @return Builder<DomainCache>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('checked_at', '<', now()->subHours(24));
    }

    /**
     * Find cache entry by domain name.
     */
    public static function findByDomain(string $domain): ?self
    {
        return self::where('domain', $domain)->first();
    }

    /**
     * Check if this cache entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->checked_at->lt(now()->subHours(24));
    }

    /**
     * Get the age of this cache entry in hours.
     */
    public function getAgeInHours(): float
    {
        return $this->checked_at->diffInHours(now(), true);
    }
}
