<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $domain
 * @property string $tld
 * @property bool $has_records
 * @property array<array-key, mixed>|null $record_types
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon $checked_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\DnsLookupCacheFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereHasRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereRecordTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereTld($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupCache whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class DnsLookupCache extends Model
{
    /** @use HasFactory<\Database\Factories\DnsLookupCacheFactory> */
    use HasFactory;

    protected $table = 'dns_lookup_cache';

    protected $fillable = [
        'domain',
        'tld',
        'has_records',
        'record_types',
        'error_message',
        'checked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'has_records' => 'boolean',
            'record_types' => 'array',
            'checked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public static function findValidCache(string $domain, string $tld): ?self
    {
        return self::where('domain', $domain)
            ->where('tld', $tld)
            ->where('expires_at', '>', now())
            ->first();
    }
}
