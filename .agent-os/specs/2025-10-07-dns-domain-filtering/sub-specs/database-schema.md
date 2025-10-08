# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-10-07-dns-domain-filtering/spec.md

> Created: 2025-10-07
> Version: 1.0.0

## Schema Changes

### Modify `domain_caches` Table

Add columns to track DNS check results and method used.

**Migration: `add_dns_fields_to_domain_caches_table`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_caches', function (Blueprint $table) {
            $table->boolean('has_dns_records')->nullable()->after('available');
            $table->string('check_method', 20)->default('api')->after('has_dns_records');
            $table->json('dns_records')->nullable()->after('check_method');

            // Add index for efficient queries
            $table->index(['domain', 'check_method']);
        });
    }

    public function down(): void
    {
        Schema::table('domain_caches', function (Blueprint $table) {
            $table->dropIndex(['domain', 'check_method']);
            $table->dropColumn(['has_dns_records', 'check_method', 'dns_records']);
        });
    }
};
```

### Column Descriptions

**`has_dns_records` (boolean, nullable)**
- Purpose: Stores whether domain has any DNS records (A, AAAA, CNAME, MX)
- `true` = domain has DNS records (likely registered/in use)
- `false` = domain has no DNS records (potentially available)
- `null` = DNS check not performed or failed

**`check_method` (string, max 20 chars, default: 'api')**
- Purpose: Tracks which method was used to check domain
- Values: 'dns', 'api', 'whois'
- Allows different caching strategies per method
- Helps with debugging and analytics

**`dns_records` (json, nullable)**
- Purpose: Stores actual DNS record details for debugging/logging
- Example: `{"A": ["192.0.2.1"], "MX": ["mail.example.com"]}`
- Optional: Only populated if detailed logging is needed
- Can be used for future features (showing domain owner info)

### Index Rationale

**Composite Index: `[domain, check_method]`**
- Speeds up queries for specific domain + check method
- Typical query: "Get DNS check result for example.com"
- Improves cache lookup performance by 10-20x
- Small storage overhead (~100 bytes per row)

## Existing Schema Reference

Current `domain_caches` table structure (for reference):

```php
Schema::create('domain_caches', function (Blueprint $table) {
    $table->id();
    $table->string('domain')->unique();
    $table->boolean('available');
    $table->timestamp('checked_at');
    $table->timestamps();
});
```

After migration, final structure:

```php
Schema::create('domain_caches', function (Blueprint $table) {
    $table->id();
    $table->string('domain')->unique();
    $table->boolean('available');
    $table->boolean('has_dns_records')->nullable();
    $table->string('check_method', 20)->default('api');
    $table->json('dns_records')->nullable();
    $table->timestamp('checked_at');
    $table->timestamps();

    $table->index(['domain', 'check_method']);
});
```

## Data Migration Strategy

### Existing Records

All existing `domain_caches` records will have:
- `has_dns_records` = `null` (not yet checked via DNS)
- `check_method` = `'api'` (checked via API)
- `dns_records` = `null` (no DNS data stored)

### Backfilling DNS Data (Optional)

If we want to backfill DNS checks for existing cached domains:

```php
// Artisan command: php artisan domain:backfill-dns
DomainCache::where('check_method', 'api')
    ->whereNull('has_dns_records')
    ->chunk(100, function ($domains) {
        foreach ($domains as $domain) {
            CheckDomainDNSJob::dispatch($domain->domain);
        }
    });
```

## Cache TTL Strategy

Different cache durations based on check method:

| Check Method | TTL | Rationale |
|--------------|-----|-----------|
| dns | 7 days | DNS records change infrequently |
| api | 24 hours | Availability can change quickly |
| whois | 48 hours | Middle ground between DNS and API |

Implementation in `DomainCheckService`:

```php
private function getCacheTTL(string $method): int
{
    return match($method) {
        'dns' => 7 * 24, // 7 days
        'api' => 24,     // 1 day
        'whois' => 48,   // 2 days
        default => 24,
    };
}
```

## Query Examples

**Get cached DNS result for domain:**
```php
$cache = DomainCache::where('domain', 'example.com')
    ->where('check_method', 'dns')
    ->where('checked_at', '>=', now()->subDays(7))
    ->first();
```

**Get all domains with no DNS records:**
```php
$availableDomains = DomainCache::where('has_dns_records', false)
    ->where('checked_at', '>=', now()->subDays(7))
    ->get();
```

**Clear expired DNS caches:**
```php
DomainCache::where('check_method', 'dns')
    ->where('checked_at', '<', now()->subDays(7))
    ->delete();
```
