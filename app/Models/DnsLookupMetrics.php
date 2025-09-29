<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DnsLookupMetrics extends Model
{
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
