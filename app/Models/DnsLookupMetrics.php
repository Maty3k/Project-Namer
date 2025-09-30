<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $batch_id
 * @property int $domains_checked
 * @property int $successful_lookups
 * @property int $failed_lookups
 * @property int $cache_hits
 * @property numeric|null $average_lookup_time
 * @property numeric $total_processing_time
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\DnsLookupMetricsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereAverageLookupTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereCacheHits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereDomainsChecked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereFailedLookups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereSuccessfulLookups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereTotalProcessingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DnsLookupMetrics whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class DnsLookupMetrics extends Model
{
    /** @use HasFactory<\Database\Factories\DnsLookupMetricsFactory> */
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'average_lookup_time' => 'decimal:3',
            'total_processing_time' => 'decimal:3',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
