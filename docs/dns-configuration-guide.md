# DNS Configuration and Tuning Guide

> Version: 1.0.0
> Last Updated: September 29, 2025

## Table of Contents

1. [Configuration Overview](#configuration-overview)
2. [Basic DNS Settings](#basic-dns-settings)
3. [Performance Optimization](#performance-optimization)
4. [Reliability and Resilience](#reliability-and-resilience)
5. [Monitoring and Alerting](#monitoring-and-alerting)
6. [Caching Configuration](#caching-configuration)
7. [Environment-Specific Tuning](#environment-specific-tuning)
8. [Troubleshooting Configuration Issues](#troubleshooting-configuration-issues)
9. [Best Practices](#best-practices)

## Configuration Overview

The DNS filtering system is configured through the `config/dns.php` file and corresponding environment variables. All settings can be customized per environment using `.env` files.

### Configuration File Location
```
config/dns.php              # Main configuration file
.env                        # Environment-specific settings
.env.example                # Example configuration with defaults
```

### Environment Variable Prefix
All DNS-related environment variables use the `DNS_` prefix for consistency and easy identification.

## Basic DNS Settings

### DNS Servers Configuration

#### Primary DNS Servers
```bash
# Primary DNS servers (comma-separated)
DNS_SERVERS=8.8.8.8,1.1.1.1

# Single server example
DNS_SERVERS=8.8.8.8

# Multiple servers example
DNS_SERVERS=8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1
```

**Recommended Values:**
- **Development**: `8.8.8.8,1.1.1.1` (Google + Cloudflare)
- **Staging**: `8.8.8.8,8.8.4.4,1.1.1.1` (Google Primary + Fallback)
- **Production**: `8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1` (Full redundancy)

#### Fallback DNS Servers
```bash
DNS_FALLBACK_ENABLED=true
DNS_FALLBACK_TIMEOUT_PRIMARY=3
DNS_FALLBACK_TIMEOUT_FALLBACK=5
DNS_FALLBACK_MAX_RETRIES_PRIMARY=1
DNS_FALLBACK_MAX_RETRIES_FALLBACK=2
DNS_FALLBACK_LOG_USAGE=true
```

**Built-in Fallback Servers:**
1. Google DNS: `8.8.8.8`, `8.8.4.4`
2. Cloudflare DNS: `1.1.1.1`, `1.0.0.1`
3. OpenDNS: `208.67.222.222`, `208.67.220.220`

### Timeout Configuration

#### Basic Timeouts
```bash
# DNS lookup timeout (seconds)
DNS_TIMEOUT=2

# Primary server timeout
DNS_FALLBACK_TIMEOUT_PRIMARY=3

# Fallback server timeout
DNS_FALLBACK_TIMEOUT_FALLBACK=5
```

**Tuning Guidelines:**
- **Low Latency Networks**: 1-2 seconds
- **Standard Networks**: 2-3 seconds
- **High Latency Networks**: 3-5 seconds
- **Mobile/Satellite**: 5-10 seconds

#### Batch Processing Settings
```bash
# Domains per batch
DNS_BATCH_SIZE=10

# Maximum concurrent lookups
DNS_CONCURRENT_LOOKUPS=5
```

**Scaling Recommendations:**
```bash
# Small applications (< 1000 domains/day)
DNS_BATCH_SIZE=5
DNS_CONCURRENT_LOOKUPS=3

# Medium applications (1000-10000 domains/day)
DNS_BATCH_SIZE=10
DNS_CONCURRENT_LOOKUPS=5

# Large applications (> 10000 domains/day)
DNS_BATCH_SIZE=20
DNS_CONCURRENT_LOOKUPS=10
```

## Performance Optimization

### Caching Configuration

#### Cache TTL Settings
```bash
# DNS result cache TTL (seconds)
DNS_CACHE_TTL=86400  # 24 hours (recommended)

# Alternative values:
DNS_CACHE_TTL=3600   # 1 hour (high-change environments)
DNS_CACHE_TTL=43200  # 12 hours (balanced)
DNS_CACHE_TTL=172800 # 48 hours (stable environments)
```

**Environment-Specific Recommendations:**
- **Development**: `3600` (1 hour) - for rapid testing
- **Staging**: `43200` (12 hours) - balanced approach
- **Production**: `86400` (24 hours) - optimal performance

#### Cache Warming Configuration
```bash
# Enable cache warming
DNS_WARMING_ENABLED=true
DNS_WARMING_BATCH_SIZE=10
DNS_WARMING_RATE_LIMIT=500  # per hour
DNS_WARMING_OFF_PEAK_ONLY=false

# Cache warming thresholds
DNS_WARMING_MIN_FREQUENCY=2
DNS_WARMING_STALE_THRESHOLD=12  # hours

# Strategy limits
DNS_WARMING_POPULAR_LIMIT=100
DNS_WARMING_TRENDING_LIMIT=10
DNS_WARMING_STALE_LIMIT=50
```

**Cache Warming Schedules:**
```bash
# Every 4 hours for popular domains
DNS_WARMING_POPULAR_INTERVAL="0 */4 * * *"

# Daily at 2 AM for trending TLDs
DNS_WARMING_TRENDING_INTERVAL="0 2 * * *"

# Every 6 hours for stale cache entries
DNS_WARMING_STALE_INTERVAL="0 */6 * * *"
```

### Memory and Resource Optimization

```bash
# Memory limit for DNS operations
DNS_MEMORY_LIMIT=128M

# For high-throughput applications
DNS_MEMORY_LIMIT=256M

# For resource-constrained environments
DNS_MEMORY_LIMIT=64M
```

### DNS Record Types Configuration

```bash
# Default record types (in config/dns.php):
'record_types' => [
    'A',      // IPv4 address
    'AAAA',   // IPv6 address
    'CNAME',  // Canonical name
    'MX',     // Mail exchange
    'NS',     // Name server
    'TXT',    // Text record
],
```

**Minimal Configuration (faster lookups):**
```php
'record_types' => ['A', 'CNAME'],
```

**Comprehensive Configuration (thorough checking):**
```php
'record_types' => ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA', 'PTR'],
```

## Reliability and Resilience

### Retry Configuration

#### Basic Retry Settings
```bash
# Enable retry mechanism
DNS_RETRY_ENABLED=true
DNS_RETRY_MAX_ATTEMPTS=3
DNS_RETRY_BASE_DELAY_MS=100
DNS_RETRY_MAX_DELAY_MS=5000
DNS_RETRY_EXPONENTIAL_BACKOFF=true
DNS_RETRY_JITTER_ENABLED=true
DNS_RETRY_JITTER_FACTOR=0.1
```

#### Error-Specific Retry Policies
```bash
# Network timeout retries
DNS_RETRY_TIMEOUT_MAX_ATTEMPTS=5
DNS_RETRY_TIMEOUT_BASE_DELAY=200

# DNS server error retries
DNS_RETRY_SERVER_ERROR_MAX_ATTEMPTS=3
DNS_RETRY_SERVER_ERROR_BASE_DELAY=500

# Rate limit retries
DNS_RETRY_RATE_LIMIT_MAX_ATTEMPTS=2
DNS_RETRY_RATE_LIMIT_BASE_DELAY=2000
```

#### Circuit Breaker Integration
```bash
DNS_RETRY_CIRCUIT_BREAKER=true
DNS_RETRY_STOP_ON_CB=true
DNS_RETRY_COLLECT_METRICS=true
DNS_RETRY_LOG_ATTEMPTS=true
DNS_RETRY_DETAILED_ANALYSIS=true
```

### Circuit Breaker Configuration

```bash
# Enable circuit breaker
DNS_CB_ENABLED=true

# Failure threshold before opening circuit
DNS_CB_FAILURE_THRESHOLD=5

# Timeout before attempting to close circuit (minutes)
DNS_CB_TIMEOUT_MINUTES=5

# Consecutive successes needed to close circuit
DNS_CB_SUCCESS_THRESHOLD=3
```

**Environment-Specific Circuit Breaker Settings:**

#### Development Environment
```bash
DNS_CB_FAILURE_THRESHOLD=10    # More lenient
DNS_CB_TIMEOUT_MINUTES=2       # Faster recovery
DNS_CB_SUCCESS_THRESHOLD=1     # Quick reset
```

#### Production Environment
```bash
DNS_CB_FAILURE_THRESHOLD=5     # Stricter
DNS_CB_TIMEOUT_MINUTES=5       # Standard recovery
DNS_CB_SUCCESS_THRESHOLD=3     # Stable reset
```

### Graceful Degradation

```bash
# Enable degradation
DNS_DEGRADATION_ENABLED=true

# Degradation strategies for different scenarios
DNS_DEGRADATION_CB_STRATEGY=cache_only      # When circuit breaker opens
DNS_DEGRADATION_ERROR_STRATEGY=pessimistic # On DNS errors
DNS_DEGRADATION_TIMEOUT_STRATEGY=cache_only # On timeouts
DNS_DEGRADATION_MANUAL_STRATEGY=optimistic  # Manual degradation
DNS_DEGRADATION_DEFAULT_STRATEGY=pessimistic

# Degradation thresholds
DNS_DEGRADATION_ERROR_THRESHOLD=50.0        # Error rate %
DNS_DEGRADATION_TIMEOUT_THRESHOLD=10000.0   # Response time ms

# Degradation notifications
DNS_DEGRADATION_LOG_ENABLED=true
DNS_DEGRADATION_ALERT_ENABLED=true
```

**Degradation Strategy Options:**
- `cache_only`: Use only cached results, no new DNS queries
- `pessimistic`: Assume all domains are taken (safest)
- `optimistic`: Assume all domains are available (fastest)
- `disabled`: Disable DNS filtering entirely

## Monitoring and Alerting

### Basic Monitoring Configuration

```bash
# Enable monitoring
DNS_ALERTS_ENABLED=true

# Alert suppression (prevents spam)
DNS_ALERTS_SUPPRESSION_WINDOW=60  # minutes
```

### Alert Thresholds

```bash
# Error rate threshold (%)
DNS_ALERT_ERROR_RATE_THRESHOLD=20.0

# Cache hit rate threshold (%)
DNS_ALERT_CACHE_HIT_RATE_THRESHOLD=50.0

# Response time threshold (ms)
DNS_ALERT_RESPONSE_TIME_THRESHOLD=5000.0

# Circuit breaker failures
DNS_ALERT_CB_FAILURES_THRESHOLD=5
```

**Environment-Specific Thresholds:**

#### Development
```bash
DNS_ALERT_ERROR_RATE_THRESHOLD=50.0      # More lenient
DNS_ALERT_RESPONSE_TIME_THRESHOLD=10000.0
DNS_ALERT_CB_FAILURES_THRESHOLD=10
```

#### Production
```bash
DNS_ALERT_ERROR_RATE_THRESHOLD=15.0      # Stricter monitoring
DNS_ALERT_RESPONSE_TIME_THRESHOLD=3000.0
DNS_ALERT_CB_FAILURES_THRESHOLD=3
```

### Notification Configuration

```bash
# Log notifications (always recommended)
DNS_ALERTS_LOG_ENABLED=true

# Email notifications
DNS_ALERTS_EMAIL_ENABLED=false

# Webhook notifications
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_ALERTS_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

## Caching Configuration

### Logging Configuration

```bash
# Basic logging
DNS_LOGGING_ENABLED=true
DNS_LOGGING_LEVEL=info

# Detailed logging options
DNS_LOGGING_METRICS=true
DNS_LOGGING_CACHE_OPS=false           # Enable for cache debugging
DNS_LOGGING_PERFORMANCE=true
DNS_LOGGING_SECURITY=true
DNS_LOGGING_BATCH=true
DNS_LOGGING_STRUCTURED=true

# Log file management
DNS_LOG_MAX_FILE_SIZE=100MB
DNS_LOG_RETENTION_DAYS=30
```

### Error Aggregation

```bash
# Prevent log spam
DNS_ERROR_AGGREGATION=true
DNS_ERROR_WINDOW=5                    # minutes
DNS_MAX_DUPLICATE_ERRORS=10

# Critical thresholds for special logging
DNS_CRITICAL_ERROR_RATE=50.0         # %
DNS_CRITICAL_RESPONSE_TIME=10000.0   # ms
DNS_CRITICAL_CB_FAILURES=5
```

**Logging Levels:**
- `debug`: Detailed debugging information
- `info`: General operational information
- `warning`: Warning conditions that should be reviewed
- `error`: Error conditions that need attention
- `critical`: Critical conditions requiring immediate action

## Environment-Specific Tuning

### Development Environment
```bash
# config/dns.php or .env.local
DNS_TIMEOUT=5
DNS_CACHE_TTL=3600
DNS_BATCH_SIZE=5
DNS_CONCURRENT_LOOKUPS=2
DNS_CB_FAILURE_THRESHOLD=10
DNS_CB_TIMEOUT_MINUTES=1
DNS_LOGGING_LEVEL=debug
DNS_LOGGING_CACHE_OPS=true
DNS_ALERTS_ENABLED=false
DNS_WARMING_ENABLED=false
```

### Staging Environment
```bash
DNS_TIMEOUT=3
DNS_CACHE_TTL=43200
DNS_BATCH_SIZE=10
DNS_CONCURRENT_LOOKUPS=5
DNS_CB_FAILURE_THRESHOLD=7
DNS_CB_TIMEOUT_MINUTES=3
DNS_LOGGING_LEVEL=info
DNS_ALERTS_ENABLED=true
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=100
```

### Production Environment
```bash
DNS_TIMEOUT=2
DNS_CACHE_TTL=86400
DNS_BATCH_SIZE=15
DNS_CONCURRENT_LOOKUPS=8
DNS_CB_FAILURE_THRESHOLD=5
DNS_CB_TIMEOUT_MINUTES=5
DNS_LOGGING_LEVEL=warning
DNS_ALERTS_ENABLED=true
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=500
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_ALERTS_WEBHOOK_URL=https://your-monitoring-system.com/webhook
```

### High-Traffic Configuration
```bash
# For applications processing > 10,000 domains/day
DNS_TIMEOUT=1
DNS_CACHE_TTL=172800              # 48 hours
DNS_BATCH_SIZE=25
DNS_CONCURRENT_LOOKUPS=15
DNS_MEMORY_LIMIT=512M
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=1000
DNS_WARMING_POPULAR_LIMIT=500
DNS_CB_FAILURE_THRESHOLD=3
DNS_ALERT_ERROR_RATE_THRESHOLD=10.0
```

## Troubleshooting Configuration Issues

### Common Configuration Problems

#### 1. Slow DNS Response Times
**Problem**: DNS lookups taking too long
**Solutions**:
```bash
# Reduce timeout
DNS_TIMEOUT=1

# Increase concurrent lookups
DNS_CONCURRENT_LOOKUPS=10

# Enable cache warming
DNS_WARMING_ENABLED=true

# Use faster DNS servers
DNS_SERVERS=1.1.1.1,8.8.8.8
```

#### 2. High Error Rates
**Problem**: Many DNS lookup failures
**Solutions**:
```bash
# Enable fallback servers
DNS_FALLBACK_ENABLED=true

# Increase retry attempts
DNS_RETRY_MAX_ATTEMPTS=5

# Adjust circuit breaker threshold
DNS_CB_FAILURE_THRESHOLD=10

# Use degradation strategy
DNS_DEGRADATION_ENABLED=true
DNS_DEGRADATION_ERROR_STRATEGY=cache_only
```

#### 3. Memory Issues
**Problem**: DNS operations consuming too much memory
**Solutions**:
```bash
# Reduce memory limit
DNS_MEMORY_LIMIT=64M

# Reduce batch size
DNS_BATCH_SIZE=5

# Reduce concurrent lookups
DNS_CONCURRENT_LOOKUPS=3

# Disable cache warming
DNS_WARMING_ENABLED=false
```

#### 4. Cache Performance Issues
**Problem**: Low cache hit rates
**Solutions**:
```bash
# Increase cache TTL
DNS_CACHE_TTL=172800

# Enable cache warming
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=1000

# Increase warming limits
DNS_WARMING_POPULAR_LIMIT=200
DNS_WARMING_STALE_LIMIT=100
```

### Debugging Configuration

#### Enable Debug Logging
```bash
DNS_LOGGING_ENABLED=true
DNS_LOGGING_LEVEL=debug
DNS_LOGGING_CACHE_OPS=true
DNS_LOGGING_PERFORMANCE=true
DNS_RETRY_LOG_ATTEMPTS=true
DNS_RETRY_DETAILED_ANALYSIS=true
```

#### Test Configuration
```bash
# Test DNS configuration
php artisan dns:test:configuration

# Test DNS server connectivity
php artisan dns:servers:test

# Check DNS health
php artisan dns:health:check

# View current configuration
php artisan dns:config:show
```

## Best Practices

### General Configuration Guidelines

#### 1. Start Conservative, Scale Up
```bash
# Initial configuration
DNS_TIMEOUT=3
DNS_BATCH_SIZE=5
DNS_CONCURRENT_LOOKUPS=3
DNS_CB_FAILURE_THRESHOLD=10

# Scale up based on monitoring
DNS_TIMEOUT=2
DNS_BATCH_SIZE=15
DNS_CONCURRENT_LOOKUPS=8
DNS_CB_FAILURE_THRESHOLD=5
```

#### 2. Monitor Before Optimizing
- Enable comprehensive monitoring first
- Collect baseline metrics for at least one week
- Make incremental changes based on data
- Monitor impact of each change

#### 3. Environment Parity with Differences
- Keep basic settings consistent across environments
- Adjust thresholds based on environment requirements
- Use stricter monitoring in production
- Enable debug features only in development

### Performance Optimization Workflow

#### Step 1: Establish Baseline
```bash
# Enable monitoring
DNS_ALERTS_ENABLED=true
DNS_LOGGING_PERFORMANCE=true
DNS_RETRY_COLLECT_METRICS=true
```

#### Step 2: Optimize Timeouts
Start with conservative timeouts and gradually reduce:
```bash
# Week 1: Conservative
DNS_TIMEOUT=5

# Week 2: Moderate
DNS_TIMEOUT=3

# Week 3+: Aggressive (if error rates remain low)
DNS_TIMEOUT=2
```

#### Step 3: Scale Concurrency
Increase concurrent operations gradually:
```bash
# Start with low concurrency
DNS_CONCURRENT_LOOKUPS=3
DNS_BATCH_SIZE=5

# Monitor memory and error rates
DNS_CONCURRENT_LOOKUPS=5
DNS_BATCH_SIZE=10

# Scale up if performance improves
DNS_CONCURRENT_LOOKUPS=8
DNS_BATCH_SIZE=15
```

#### Step 4: Optimize Caching
Implement caching strategy based on usage patterns:
```bash
# Start with standard caching
DNS_CACHE_TTL=86400

# Enable warming for high-traffic sites
DNS_WARMING_ENABLED=true

# Optimize warming based on hit rates
DNS_WARMING_POPULAR_LIMIT=100  # Adjust based on traffic
```

### Security Best Practices

#### 1. DNS Server Selection
- Use reputable public DNS servers
- Avoid DNS servers from unknown providers
- Consider geographic proximity for performance
- Have multiple fallback options

#### 2. Input Validation
```php
// Already implemented in the system
'record_types' => [
    'A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT'  // Limit to necessary types
],
```

#### 3. Rate Limiting
```bash
# Prevent abuse
DNS_WARMING_RATE_LIMIT=500
DNS_RETRY_RATE_LIMIT_BASE_DELAY=2000

# Monitor for suspicious patterns
DNS_LOGGING_SECURITY=true
```

### Maintenance Best Practices

#### 1. Regular Health Checks
```bash
# Schedule regular health checks
php artisan schedule:run

# Monitor health status
php artisan dns:health:check --report
```

#### 2. Cache Maintenance
```bash
# Regular cache cleanup
php artisan dns:cache:cleanup

# Cache optimization
php artisan dns:cache:optimize
```

#### 3. Log Management
```bash
# Configure log retention
DNS_LOG_RETENTION_DAYS=30

# Monitor log file sizes
DNS_LOG_MAX_FILE_SIZE=100MB

# Enable log rotation
DNS_ERROR_AGGREGATION=true
```

### Configuration Validation

Before deploying configuration changes, validate them:

```bash
# Test configuration syntax
php artisan config:cache

# Test DNS connectivity
php artisan dns:servers:test

# Validate DNS service health
php artisan dns:health:check

# Test performance with new settings
php artisan dns:test:performance --duration=60
```

## Configuration Examples

### Example 1: Small Business Website
```bash
# .env configuration for small business
DNS_SERVERS=8.8.8.8,1.1.1.1
DNS_TIMEOUT=3
DNS_CACHE_TTL=86400
DNS_BATCH_SIZE=5
DNS_CONCURRENT_LOOKUPS=3
DNS_CB_FAILURE_THRESHOLD=7
DNS_ALERTS_ENABLED=true
DNS_LOGGING_LEVEL=info
DNS_WARMING_ENABLED=false
```

### Example 2: SaaS Platform
```bash
# .env configuration for SaaS platform
DNS_SERVERS=8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1
DNS_TIMEOUT=2
DNS_CACHE_TTL=86400
DNS_BATCH_SIZE=15
DNS_CONCURRENT_LOOKUPS=10
DNS_CB_FAILURE_THRESHOLD=5
DNS_ALERTS_ENABLED=true
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_LOGGING_LEVEL=warning
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=1000
DNS_MEMORY_LIMIT=256M
```

### Example 3: Enterprise Application
```bash
# .env configuration for enterprise
DNS_SERVERS=8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1,208.67.222.222
DNS_TIMEOUT=1
DNS_CACHE_TTL=172800
DNS_BATCH_SIZE=25
DNS_CONCURRENT_LOOKUPS=15
DNS_CB_FAILURE_THRESHOLD=3
DNS_ALERTS_ENABLED=true
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_ALERTS_EMAIL_ENABLED=true
DNS_LOGGING_LEVEL=error
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=2000
DNS_MEMORY_LIMIT=512M
DNS_DEGRADATION_ENABLED=true
DNS_ALERT_ERROR_RATE_THRESHOLD=10.0
```

This configuration guide provides comprehensive coverage of all DNS system settings and tuning options. Regular monitoring and gradual optimization based on real-world usage patterns will help achieve optimal performance for your specific use case.