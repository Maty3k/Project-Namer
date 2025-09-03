<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * AI generation result cache model.
 * 
 * Stores AI generation results to avoid repeated API calls
 * and improve application performance.
 *
 * @property int $id
 * @property string $input_hash
 * @property string $business_description
 * @property string $mode
 * @property bool $deep_thinking
 * @property array<array-key, mixed> $generated_names
 * @property \Illuminate\Support\Carbon $cached_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder<static>|GenerationCache expired()
 * @method static Builder<static>|GenerationCache fresh()
 * @method static Builder<static>|GenerationCache newModelQuery()
 * @method static Builder<static>|GenerationCache newQuery()
 * @method static Builder<static>|GenerationCache query()
 * @method static Builder<static>|GenerationCache whereBusinessDescription($value)
 * @method static Builder<static>|GenerationCache whereCachedAt($value)
 * @method static Builder<static>|GenerationCache whereCreatedAt($value)
 * @method static Builder<static>|GenerationCache whereDeepThinking($value)
 * @method static Builder<static>|GenerationCache whereGeneratedNames($value)
 * @method static Builder<static>|GenerationCache whereId($value)
 * @method static Builder<static>|GenerationCache whereInputHash($value)
 * @method static Builder<static>|GenerationCache whereMode($value)
 * @method static Builder<static>|GenerationCache whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class GenerationCache extends Model
{
    protected $table = 'generation_caches';

    protected $fillable = [
        'input_hash',
        'business_description',
        'mode',
        'deep_thinking',
        'generated_names',
        'cached_at',
    ];

    protected function casts(): array
    {
        return [
            'deep_thinking' => 'boolean',
            'generated_names' => 'array',
            'cached_at' => 'datetime',
        ];
    }

    /**
     * Scope to get fresh cache entries (not expired).
     *
     * @param  Builder<GenerationCache>  $query
     * @return Builder<GenerationCache>
     */
    protected function scopeFresh(Builder $query): Builder
    {
        return $query->where('cached_at', '>=', now()->subHours(24));
    }

    /**
     * Scope to get expired cache entries.
     *
     * @param  Builder<GenerationCache>  $query
     * @return Builder<GenerationCache>
     */
    protected function scopeExpired(Builder $query): Builder
    {
        return $query->where('cached_at', '<', now()->subHours(24));
    }

    /**
     * Find cache entry by input hash.
     */
    public static function findByHash(string $hash): ?self
    {
        return self::query()->fresh()->where('input_hash', $hash)->first();
    }

    /**
     * Check if this cache entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->cached_at->lt(now()->subHours(24));
    }

    /**
     * Get the age of this cache entry in hours.
     */
    public function getAgeInHours(): float
    {
        return $this->cached_at->diffInHours(now(), true);
    }

    /**
     * Generate cache key hash from input parameters.
     */
    public static function generateHash(string $businessDescription, string $mode, bool $deepThinking): string
    {
        $data = json_encode([
            'business_description' => trim(strtolower($businessDescription)),
            'mode' => $mode,
            'deep_thinking' => $deepThinking,
        ]);

        if ($data === false) {
            throw new \RuntimeException('Failed to encode cache key data');
        }

        return hash('sha256', $data);
    }
}
