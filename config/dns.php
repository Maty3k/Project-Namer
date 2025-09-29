<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | DNS Servers
    |--------------------------------------------------------------------------
    |
    | List of DNS servers to use for domain lookups. Multiple servers
    | can be specified as a comma-separated list. The system will use
    | these servers in order, falling back to public DNS if needed.
    |
    */
    'servers' => env('DNS_SERVERS', '8.8.8.8,1.1.1.1'),

    /*
    |--------------------------------------------------------------------------
    | DNS Lookup Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for DNS lookup operations. This should be kept
    | relatively low to prevent blocking user requests for too long.
    |
    */
    'timeout' => (int) env('DNS_TIMEOUT', 2),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live for DNS lookup cache in seconds. DNS results will be
    | cached for this duration to avoid repeated lookups for the same domains.
    | Default is 24 hours (86400 seconds).
    |
    */
    'cache_ttl' => (int) env('DNS_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Batch Processing Size
    |--------------------------------------------------------------------------
    |
    | Number of domains to process in a single batch when performing
    | bulk DNS lookups. Smaller batches provide better user feedback
    | but may take longer overall.
    |
    */
    'batch_size' => (int) env('DNS_BATCH_SIZE', 10),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retries
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts for failed DNS lookups.
    | Each retry uses exponential backoff to avoid overwhelming
    | DNS servers.
    |
    */
    'max_retries' => (int) env('DNS_MAX_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the circuit breaker pattern to protect against
    | cascading failures when DNS services are unavailable.
    |
    */
    'circuit_breaker' => [
        'enabled' => env('DNS_CB_ENABLED', true),
        'failure_threshold' => (int) env('DNS_CB_FAILURE_THRESHOLD', 5),
        'timeout_minutes' => (int) env('DNS_CB_TIMEOUT_MINUTES', 5),
        'success_threshold' => (int) env('DNS_CB_SUCCESS_THRESHOLD', 3),
    ],

    // Legacy configuration for backward compatibility
    'circuit_breaker_threshold' => (int) env('DNS_CB_THRESHOLD', 5),
    'circuit_breaker_reset_time' => (int) env('DNS_CB_RESET_TIME', 300),

    /*
    |--------------------------------------------------------------------------
    | DNS Record Types
    |--------------------------------------------------------------------------
    |
    | Array of DNS record types to check when determining if a domain
    | has existing records. If any of these record types exist,
    | the domain will be considered "taken".
    |
    */
    'record_types' => [
        'A',      // IPv4 address
        'AAAA',   // IPv6 address
        'CNAME',  // Canonical name
        'MX',     // Mail exchange
        'NS',     // Name server
        'TXT',    // Text record
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback DNS Servers
    |--------------------------------------------------------------------------
    |
    | List of public DNS servers to use as fallback when primary
    | DNS servers are unavailable. These are well-known, reliable
    | public DNS services.
    |
    */
    'fallback_servers' => [
        '8.8.8.8',        // Google Public DNS
        '8.8.4.4',        // Google Public DNS Secondary
        '1.1.1.1',        // Cloudflare DNS
        '1.0.0.1',        // Cloudflare DNS Secondary
        '208.67.222.222', // OpenDNS
        '208.67.220.220', // OpenDNS Secondary
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for DNS fallback behavior when primary servers fail.
    |
    */
    'fallback' => [
        'enabled' => env('DNS_FALLBACK_ENABLED', true),
        'timeout_primary' => (int) env('DNS_FALLBACK_TIMEOUT_PRIMARY', 3), // seconds
        'timeout_fallback' => (int) env('DNS_FALLBACK_TIMEOUT_FALLBACK', 5), // seconds
        'max_retries_primary' => (int) env('DNS_FALLBACK_MAX_RETRIES_PRIMARY', 1),
        'max_retries_fallback' => (int) env('DNS_FALLBACK_MAX_RETRIES_FALLBACK', 2),
        'log_fallback_usage' => env('DNS_FALLBACK_LOG_USAGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for DNS lookup logging and monitoring.
    |
    */
    'logging' => [
        'enabled' => env('DNS_LOGGING_ENABLED', true),
        'level' => env('DNS_LOGGING_LEVEL', 'info'),
        'include_metrics' => env('DNS_LOGGING_METRICS', true),
        'log_cache_operations' => env('DNS_LOGGING_CACHE_OPS', false),
        'log_performance_metrics' => env('DNS_LOGGING_PERFORMANCE', true),
        'log_security_events' => env('DNS_LOGGING_SECURITY', true),
        'log_batch_operations' => env('DNS_LOGGING_BATCH', true),
        'structured_logging' => env('DNS_LOGGING_STRUCTURED', true),

        // Log rotation and retention
        'max_file_size' => env('DNS_LOG_MAX_FILE_SIZE', '100MB'),
        'retention_days' => (int) env('DNS_LOG_RETENTION_DAYS', 30),

        // Error aggregation settings
        'error_aggregation' => [
            'enabled' => env('DNS_ERROR_AGGREGATION', true),
            'window_minutes' => (int) env('DNS_ERROR_WINDOW', 5),
            'max_duplicate_errors' => (int) env('DNS_MAX_DUPLICATE_ERRORS', 10),
        ],

        // Alert thresholds for critical logging
        'critical_thresholds' => [
            'error_rate_percent' => (float) env('DNS_CRITICAL_ERROR_RATE', 50.0),
            'response_time_ms' => (float) env('DNS_CRITICAL_RESPONSE_TIME', 10000.0),
            'circuit_breaker_failures' => (int) env('DNS_CRITICAL_CB_FAILURES', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize DNS lookup performance and resource usage.
    |
    */
    'performance' => [
        'concurrent_lookups' => (int) env('DNS_CONCURRENT_LOOKUPS', 5),
        'memory_limit' => env('DNS_MEMORY_LIMIT', '128M'),
        'enable_cache_warming' => env('DNS_CACHE_WARMING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for proactive cache warming of popular domains to improve
    | cache hit rates and reduce DNS lookup latency.
    |
    */
    'warming' => [
        'enabled' => env('DNS_WARMING_ENABLED', true),
        'batch_size' => (int) env('DNS_WARMING_BATCH_SIZE', 10),
        'rate_limit_per_hour' => (int) env('DNS_WARMING_RATE_LIMIT', 500),
        'off_peak_only' => env('DNS_WARMING_OFF_PEAK_ONLY', false),
        'min_frequency' => (int) env('DNS_WARMING_MIN_FREQUENCY', 2),
        'stale_threshold_hours' => (int) env('DNS_WARMING_STALE_THRESHOLD', 12),

        'schedule' => [
            'popular_domains_interval' => env('DNS_WARMING_POPULAR_INTERVAL', '0 */4 * * *'), // Every 4 hours
            'trending_tlds_interval' => env('DNS_WARMING_TRENDING_INTERVAL', '0 2 * * *'),   // Daily at 2 AM
            'stale_rewarming_interval' => env('DNS_WARMING_STALE_INTERVAL', '0 */6 * * *'),  // Every 6 hours
        ],

        'strategies' => [
            'popular_domains_limit' => (int) env('DNS_WARMING_POPULAR_LIMIT', 100),
            'trending_tlds_limit' => (int) env('DNS_WARMING_TRENDING_LIMIT', 10),
            'stale_rewarming_limit' => (int) env('DNS_WARMING_STALE_LIMIT', 50),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring & Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for DNS service health monitoring and alerting system.
    | Alerts are triggered when metrics exceed defined thresholds.
    |
    */
    'alerts' => [
        'enabled' => env('DNS_ALERTS_ENABLED', true),
        'suppression_window' => (int) env('DNS_ALERTS_SUPPRESSION_WINDOW', 60), // minutes

        'thresholds' => [
            'error_rate' => (float) env('DNS_ALERT_ERROR_RATE_THRESHOLD', 20.0), // %
            'cache_hit_rate' => (float) env('DNS_ALERT_CACHE_HIT_RATE_THRESHOLD', 50.0), // %
            'response_time' => (float) env('DNS_ALERT_RESPONSE_TIME_THRESHOLD', 5000.0), // ms
            'circuit_breaker_failures' => (int) env('DNS_ALERT_CB_FAILURES_THRESHOLD', 5),
        ],

        'notifications' => [
            'log_enabled' => env('DNS_ALERTS_LOG_ENABLED', true),
            'email_enabled' => env('DNS_ALERTS_EMAIL_ENABLED', false),
            'webhook_enabled' => env('DNS_ALERTS_WEBHOOK_ENABLED', false),
            'webhook_url' => env('DNS_ALERTS_WEBHOOK_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Graceful Degradation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for DNS service degradation modes when DNS services are
    | unavailable or experiencing issues. Different strategies can be
    | configured for different failure scenarios.
    |
    */
    'degradation' => [
        'enabled' => env('DNS_DEGRADATION_ENABLED', true),

        // Strategy options: cache_only, pessimistic, optimistic, disabled
        'circuit_breaker_strategy' => env('DNS_DEGRADATION_CB_STRATEGY', 'cache_only'),
        'error_strategy' => env('DNS_DEGRADATION_ERROR_STRATEGY', 'pessimistic'),
        'timeout_strategy' => env('DNS_DEGRADATION_TIMEOUT_STRATEGY', 'cache_only'),
        'manual_strategy' => env('DNS_DEGRADATION_MANUAL_STRATEGY', 'optimistic'),
        'default_strategy' => env('DNS_DEGRADATION_DEFAULT_STRATEGY', 'pessimistic'),

        'thresholds' => [
            'error_rate_degradation' => (float) env('DNS_DEGRADATION_ERROR_THRESHOLD', 50.0), // %
            'response_time_degradation' => (float) env('DNS_DEGRADATION_TIMEOUT_THRESHOLD', 10000.0), // ms
        ],

        'notifications' => [
            'log_degradation_events' => env('DNS_DEGRADATION_LOG_ENABLED', true),
            'alert_on_degradation' => env('DNS_DEGRADATION_ALERT_ENABLED', true),
        ],
    ],
];