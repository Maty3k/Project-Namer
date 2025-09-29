<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DnsLookupCache extends Model
{
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
        return static::where('domain', $domain)
            ->where('tld', $tld)
            ->where('expires_at', '>', now())
            ->first();
    }
}
