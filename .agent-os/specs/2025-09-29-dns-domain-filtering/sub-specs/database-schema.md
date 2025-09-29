# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-09-29-dns-domain-filtering/spec.md

> Created: 2025-09-29
> Version: 1.0.0

## Database Changes

### New Table: dns_lookup_cache

Store DNS lookup results with caching information.

```sql
CREATE TABLE dns_lookup_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    tld VARCHAR(10) NOT NULL,
    has_records BOOLEAN NOT NULL DEFAULT FALSE,
    record_types JSON NULL,
    error_message TEXT NULL,
    checked_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_domain_tld (domain, tld),
    INDEX idx_expires_at (expires_at),
    INDEX idx_checked_at (checked_at),
    INDEX idx_has_records (has_records)
);
```

### Modified Table: domain_suggestions

Add DNS lookup status tracking to existing domain suggestions.

```sql
ALTER TABLE domain_suggestions
ADD COLUMN dns_checked BOOLEAN DEFAULT FALSE AFTER available,
ADD COLUMN dns_has_records BOOLEAN NULL AFTER dns_checked,
ADD COLUMN dns_checked_at TIMESTAMP NULL AFTER dns_has_records,
ADD INDEX idx_dns_checked (dns_checked),
ADD INDEX idx_dns_has_records (dns_has_records);
```

### New Table: dns_lookup_metrics

Track DNS lookup performance and reliability metrics.

```sql
CREATE TABLE dns_lookup_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(36) NOT NULL,
    domains_checked INT UNSIGNED NOT NULL DEFAULT 0,
    successful_lookups INT UNSIGNED NOT NULL DEFAULT 0,
    failed_lookups INT UNSIGNED NOT NULL DEFAULT 0,
    cache_hits INT UNSIGNED NOT NULL DEFAULT 0,
    average_lookup_time DECIMAL(8,3) NULL,
    total_processing_time DECIMAL(8,3) NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_batch_id (batch_id),
    INDEX idx_completed_at (completed_at)
);
```

## Migration Scripts

### Migration: 2025_09_29_000001_create_dns_lookup_cache_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_lookup_cache', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('tld', 10);
            $table->boolean('has_records')->default(false);
            $table->json('record_types')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['domain', 'tld'], 'unique_domain_tld');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('checked_at', 'idx_checked_at');
            $table->index('has_records', 'idx_has_records');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_lookup_cache');
    }
};
```

### Migration: 2025_09_29_000002_add_dns_fields_to_domain_suggestions.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_suggestions', function (Blueprint $table) {
            $table->boolean('dns_checked')->default(false)->after('available');
            $table->boolean('dns_has_records')->nullable()->after('dns_checked');
            $table->timestamp('dns_checked_at')->nullable()->after('dns_has_records');

            $table->index('dns_checked', 'idx_dns_checked');
            $table->index('dns_has_records', 'idx_dns_has_records');
        });
    }

    public function down(): void
    {
        Schema::table('domain_suggestions', function (Blueprint $table) {
            $table->dropIndex('idx_dns_checked');
            $table->dropIndex('idx_dns_has_records');
            $table->dropColumn(['dns_checked', 'dns_has_records', 'dns_checked_at']);
        });
    }
};
```

### Migration: 2025_09_29_000003_create_dns_lookup_metrics_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_lookup_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('domains_checked')->default(0);
            $table->unsignedInteger('successful_lookups')->default(0);
            $table->unsignedInteger('failed_lookups')->default(0);
            $table->unsignedInteger('cache_hits')->default(0);
            $table->decimal('average_lookup_time', 8, 3)->nullable();
            $table->decimal('total_processing_time', 8, 3);
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index('batch_id', 'idx_batch_id');
            $table->index('completed_at', 'idx_completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_lookup_metrics');
    }
};
```

## Eloquent Models

### DnsLookupCache Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DnsLookupCache extends Model
{
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

    protected $casts = [
        'has_records' => 'boolean',
        'record_types' => 'array',
        'checked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

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
```

### DnsLookupMetrics Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DnsLookupMetrics extends Model
{
    protected $table = 'dns_lookup_metrics';

    protected $fillable = [
        'batch_id',
        'domains_checked',
        'successful_lookups',
        'failed_lookups',
        'cache_hits',
        'average_lookup_time',
        'total_processing_time',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'average_lookup_time' => 'decimal:3',
        'total_processing_time' => 'decimal:3',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
```

## Performance Considerations

### Indexing Strategy
- Composite unique index on (domain, tld) for fast cache lookups
- Index on expires_at for efficient cache cleanup
- Index on has_records for filtering queries
- Partitioning by created_at for large-scale deployments

### Data Retention
- Automatically clean expired cache entries (older than 24 hours)
- Archive DNS metrics after 90 days
- Implement database maintenance commands

### Optimization
- Use database-level JSON queries for record_types filtering
- Consider read replicas for high-traffic scenarios
- Implement connection pooling for concurrent lookups