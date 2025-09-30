# DNS Troubleshooting Guide

> Version: 1.0.0
> Last Updated: September 29, 2025

## Table of Contents

1. [Quick Diagnostic Commands](#quick-diagnostic-commands)
2. [Common Issues and Solutions](#common-issues-and-solutions)
3. [Performance Issues](#performance-issues)
4. [Cache Problems](#cache-problems)
5. [Queue and Worker Issues](#queue-and-worker-issues)
6. [Network and Connectivity Issues](#network-and-connectivity-issues)
7. [Database Issues](#database-issues)
8. [Configuration Problems](#configuration-problems)
9. [Monitoring and Logging](#monitoring-and-logging)
10. [Emergency Procedures](#emergency-procedures)

## Quick Diagnostic Commands

### Essential Health Checks
```bash
# Check overall DNS system health
php artisan dns:health:check

# Test basic DNS functionality
php artisan dns:test:basic

# Check DNS server connectivity
php artisan dns:servers:test

# Verify queue workers are running
php artisan queue:monitor dns

# Check application health endpoints
curl -s https://your-domain.com/health/dns | jq .
```

### System Status Overview
```bash
# Check all DNS workers
sudo supervisorctl status | grep dns

# Check system resources
free -h && df -h

# Check recent DNS errors
tail -50 storage/logs/laravel.log | grep -i "dns.*error"

# Check circuit breaker status
php artisan tinker -c "app('App\Services\DnsCircuitBreakerService')->isOpen()"
```

### Performance Quick Check
```bash
# Test DNS performance
php artisan dns:test:performance --duration=60

# Check cache hit rate
php artisan dns:metrics:report --period=1h | grep cache

# Monitor queue in real-time
watch -n 2 'php artisan queue:monitor dns'
```

## Common Issues and Solutions

### Issue 1: DNS Lookups Always Return "Unknown"

**Symptoms:**
- All domain checks return "unknown" status
- No DNS records found even for known domains like google.com
- High error rates in logs

**Diagnosis:**
```bash
# Test manual DNS lookup
nslookup google.com 8.8.8.8

# Check DNS service configuration
php artisan config:show dns.servers

# Test DNS service directly
php artisan tinker
>>> app('App\Services\DnsLookupService')->checkDomain('google.com')
```

**Common Causes & Solutions:**

#### Cause: DNS Servers Unreachable
```bash
# Test connectivity to DNS servers
ping -c 3 8.8.8.8
nc -zv 8.8.8.8 53

# Solution: Update DNS servers
# Edit .env file
DNS_SERVERS=1.1.1.1,8.8.8.8

php artisan config:cache
```

#### Cause: Firewall Blocking DNS Queries
```bash
# Check firewall rules
sudo ufw status
sudo iptables -L OUTPUT | grep 53

# Solution: Allow DNS traffic
sudo ufw allow out 53
```

#### Cause: PHP DNS Extension Issues
```bash
# Check if DNS functions are available
php -m | grep -i dns
php -r "var_dump(function_exists('dns_get_record'));"

# Solution: Install missing extensions (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install php8.4-dev
```

### Issue 2: DNS Checks Taking Too Long

**Symptoms:**
- DNS lookups timeout frequently
- Response times > 10 seconds
- Queue jobs failing due to timeouts

**Diagnosis:**
```bash
# Check current timeout settings
php artisan config:show dns.timeout

# Test response times
time php artisan dns:test:basic

# Check for DNS server delays
dig @8.8.8.8 google.com +time=1
```

**Solutions:**

#### Reduce Timeout Values
```bash
# Update .env
DNS_TIMEOUT=1
DNS_FALLBACK_TIMEOUT_PRIMARY=2
DNS_FALLBACK_TIMEOUT_FALLBACK=3

php artisan config:cache
```

#### Enable Faster DNS Servers
```bash
# Use Cloudflare DNS (often faster)
DNS_SERVERS=1.1.1.1,1.0.0.1

# Or use multiple servers for redundancy
DNS_SERVERS=1.1.1.1,8.8.8.8,8.8.4.4
```

#### Optimize Concurrent Processing
```bash
# Increase concurrent lookups
DNS_CONCURRENT_LOOKUPS=10
DNS_BATCH_SIZE=20
```

### Issue 3: High DNS Error Rates

**Symptoms:**
- Error rate > 25% in monitoring
- Many failed DNS lookups in logs
- Circuit breaker frequently opening

**Diagnosis:**
```bash
# Check error types
grep -i "dns.*error" storage/logs/laravel.log | tail -20

# Check circuit breaker status
php artisan dns:health:check --detailed

# Analyze error patterns
php artisan dns:metrics:report --period=4h --errors
```

**Solutions:**

#### Rate Limiting Issues
```bash
# Check if hitting rate limits
grep -i "rate.limit\|too.many" storage/logs/laravel.log

# Solution: Reduce request frequency
DNS_WARMING_RATE_LIMIT=100
DNS_BATCH_SIZE=5
DNS_CONCURRENT_LOOKUPS=3
```

#### DNS Server Overload
```bash
# Distribute load across multiple servers
DNS_SERVERS=8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1,208.67.222.222

# Enable fallback servers
DNS_FALLBACK_ENABLED=true
```

#### Enable Graceful Degradation
```bash
# Use cache-only mode during high errors
php artisan dns:degradation:enable --strategy=cache_only

# Or set automatic degradation
DNS_DEGRADATION_ENABLED=true
DNS_DEGRADATION_ERROR_THRESHOLD=20.0
```

### Issue 4: Circuit Breaker Constantly Opening

**Symptoms:**
- Circuit breaker opens frequently
- DNS service becomes unavailable
- Alerts for circuit breaker failures

**Diagnosis:**
```bash
# Check circuit breaker settings
php artisan config:show dns.circuit_breaker

# Review failure patterns
grep "circuit.breaker" storage/logs/laravel.log | tail -10

# Test circuit breaker recovery
php artisan dns:test:circuit-breaker
```

**Solutions:**

#### Adjust Thresholds
```bash
# More lenient settings for unstable networks
DNS_CB_FAILURE_THRESHOLD=10
DNS_CB_TIMEOUT_MINUTES=2
DNS_CB_SUCCESS_THRESHOLD=1
```

#### Manual Recovery
```bash
# Force circuit breaker closed
php artisan dns:recover --emergency

# Or reset circuit breaker
php artisan tinker
>>> app('App\Services\DnsCircuitBreakerService')->forceClose()
```

## Performance Issues

### Issue: Slow DNS Response Times

**Symptoms:**
- Average response time > 3 seconds
- P95 response time > 5 seconds
- User complaints about slow name generation

**Diagnosis:**
```bash
# Measure current performance
php artisan dns:test:performance --duration=300

# Check for slow queries
grep "processing_time_ms" storage/logs/laravel.log | grep -E "[5-9][0-9]{3,}|[0-9]{5,}"

# Analyze cache performance
php artisan dns:cache:stats
```

**Performance Optimization Steps:**

#### 1. Optimize DNS Servers
```bash
# Use fastest DNS servers for your location
DNS_SERVERS=1.1.1.1,8.8.8.8  # Usually fastest globally

# Test different servers
for server in 1.1.1.1 8.8.8.8 208.67.222.222; do
    echo "Testing $server:"
    time dig @$server google.com +short
done
```

#### 2. Increase Concurrency
```bash
# Process more domains simultaneously
DNS_CONCURRENT_LOOKUPS=15
DNS_BATCH_SIZE=25

# But monitor memory usage
DNS_MEMORY_LIMIT=512M
```

#### 3. Optimize Caching
```bash
# Increase cache TTL
DNS_CACHE_TTL=172800  # 48 hours

# Enable aggressive cache warming
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=1000
DNS_WARMING_POPULAR_LIMIT=500
```

#### 4. Database Query Optimization
```bash
# Add indexes for DNS tables
php artisan tinker
>>> DB::statement('CREATE INDEX idx_dns_cache_domain_expires ON dns_lookup_cache(domain, expires_at)');
>>> DB::statement('CREATE INDEX idx_name_suggestions_dns ON name_suggestions(dns_checked, dns_has_records)');
```

### Issue: High Memory Usage

**Symptoms:**
- DNS workers consuming > 512MB memory
- Out of memory errors in logs
- Worker processes being killed

**Diagnosis:**
```bash
# Check worker memory usage
ps aux | grep "queue:work" | grep dns

# Check PHP memory limits
php -i | grep memory_limit

# Monitor memory during operation
top -p $(pgrep -f "queue:work.*dns")
```

**Solutions:**

#### Reduce Memory Footprint
```bash
# Lower memory limits
DNS_MEMORY_LIMIT=256M

# Reduce batch sizes
DNS_BATCH_SIZE=10
DNS_CONCURRENT_LOOKUPS=5

# Restart workers more frequently
php artisan queue:restart
```

#### Optimize PHP Configuration
```ini
# In php.ini or pool configuration
memory_limit = 512M
max_execution_time = 60
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

## Cache Problems

### Issue: Low Cache Hit Rate

**Symptoms:**
- Cache hit rate < 50%
- High DNS query volume
- Poor performance despite caching

**Diagnosis:**
```bash
# Check cache statistics
php artisan dns:cache:stats

# Analyze cache patterns
redis-cli info stats
redis-cli --latency -h localhost -p 6379

# Check cache configuration
php artisan config:show cache
```

**Solutions:**

#### Cache Configuration Optimization
```bash
# Increase cache TTL
DNS_CACHE_TTL=259200  # 72 hours

# Enable cache warming
DNS_WARMING_ENABLED=true
DNS_WARMING_POPULAR_LIMIT=1000
```

#### Redis Optimization
```bash
# Increase Redis memory
# In redis.conf:
maxmemory 4gb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis
```

#### Cache Warming Strategy
```bash
# Warm cache with popular domains
php artisan dns:cache:warm --popular --limit=500

# Set up aggressive warming schedule
# In app/Console/Kernel.php:
$schedule->command('dns:cache:warm')->hourly();
```

### Issue: Cache Corruption or Inconsistency

**Symptoms:**
- Incorrect DNS results returned
- Cache returning stale data
- Inconsistent domain availability

**Diagnosis:**
```bash
# Check cache integrity
php artisan dns:cache:verify

# Compare cache vs live results
php artisan dns:test:cache-consistency

# Check cache keys
redis-cli keys "dns:*" | head -10
```

**Solutions:**

#### Clear and Rebuild Cache
```bash
# Clear DNS cache
php artisan dns:cache:clear

# Clear all application caches
php artisan cache:clear
php artisan config:cache

# Warm cache with fresh data
php artisan dns:cache:warm --force
```

#### Verify Cache Settings
```bash
# Check cache driver configuration
php artisan config:show cache.default
redis-cli config get "*memory*"

# Test cache operations
php artisan tinker
>>> Cache::put('test', 'value', 3600)
>>> Cache::get('test')
```

## Queue and Worker Issues

### Issue: DNS Jobs Not Processing

**Symptoms:**
- Queue depth continuously growing
- DNS checks stuck in "checking" state
- No job processing activity

**Diagnosis:**
```bash
# Check queue status
php artisan queue:monitor dns

# Check worker processes
sudo supervisorctl status | grep dns
ps aux | grep "queue:work.*dns"

# Check job failures
php artisan queue:failed
```

**Solutions:**

#### Restart Workers
```bash
# Restart DNS workers
sudo supervisorctl restart project-namer-dns-worker:*

# Or restart all workers
php artisan queue:restart
```

#### Check Worker Configuration
```bash
# Verify supervisor configuration
sudo cat /etc/supervisor/conf.d/project-namer-dns-worker.conf

# Check for worker errors
sudo tail -50 /var/log/supervisor/supervisord.log
```

#### Scale Workers
```bash
# Increase worker count in supervisor config
numprocs=6

# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update
```

### Issue: Job Failures and Retries

**Symptoms:**
- High job failure rate
- Jobs constantly retrying
- Failed job queue growing

**Diagnosis:**
```bash
# Check failed jobs
php artisan queue:failed

# Analyze failure patterns
grep -i "job.*failed" storage/logs/laravel.log | tail -20

# Check retry configuration
php artisan config:show queue.connections.redis.retry_after
```

**Solutions:**

#### Adjust Job Configuration
```php
// In CheckDomainDnsJob
public int $timeout = 60;      // Increase timeout
public int $tries = 5;         // More retry attempts
public int $maxExceptions = 3; // Allow more exceptions
public int $backoff = 10;      // Longer backoff
```

#### Retry Failed Jobs
```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry specific failed job
php artisan queue:retry {job-id}

# Clear old failed jobs
php artisan queue:flush
```

## Network and Connectivity Issues

### Issue: DNS Server Connectivity Problems

**Symptoms:**
- Timeouts connecting to DNS servers
- Network unreachable errors
- Intermittent connectivity

**Diagnosis:**
```bash
# Test DNS server connectivity
for server in 8.8.8.8 1.1.1.1; do
    echo "Testing $server:"
    nc -zv $server 53
    ping -c 3 $server
done

# Check routing
traceroute 8.8.8.8
ip route show

# Check DNS resolution
nslookup google.com
dig @8.8.8.8 google.com
```

**Solutions:**

#### Network Configuration
```bash
# Check network interface status
ip addr show
sudo systemctl status networking

# Restart networking if needed
sudo systemctl restart networking
```

#### Firewall Rules
```bash
# Check current firewall status
sudo ufw status verbose

# Allow DNS traffic
sudo ufw allow out 53/udp
sudo ufw allow out 53/tcp

# Check iptables rules
sudo iptables -L OUTPUT -n | grep 53
```

#### DNS Server Selection
```bash
# Use multiple reliable servers
DNS_SERVERS=1.1.1.1,8.8.8.8,208.67.222.222,208.67.220.220

# Enable fallback configuration
DNS_FALLBACK_ENABLED=true
DNS_FALLBACK_TIMEOUT_PRIMARY=2
DNS_FALLBACK_TIMEOUT_FALLBACK=5
```

### Issue: SSL/TLS Certificate Problems

**Symptoms:**
- HTTPS health checks failing
- SSL certificate errors
- Unable to access monitoring endpoints

**Diagnosis:**
```bash
# Test SSL certificate
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Check certificate expiration
echo | openssl s_client -connect your-domain.com:443 2>/dev/null | openssl x509 -noout -dates

# Test health endpoints
curl -v https://your-domain.com/health/dns
```

**Solutions:**

#### Certificate Renewal
```bash
# Renew Let's Encrypt certificates
sudo certbot renew --dry-run

# Restart web server
sudo systemctl restart nginx
```

#### Certificate Configuration
```bash
# Check nginx SSL configuration
sudo nginx -t
sudo cat /etc/nginx/sites-available/your-site
```

## Database Issues

### Issue: Database Connection Problems

**Symptoms:**
- Database connection errors in logs
- DNS cache not being saved
- Metrics not being recorded

**Diagnosis:**
```bash
# Test database connection
php artisan tinker
>>> DB::select('SELECT 1 as test')

# Check database status
mysql -u username -p -e "SHOW PROCESSLIST;"
mysql -u username -p -e "SHOW STATUS LIKE 'Threads_connected';"

# Check connection pool
php artisan config:show database.connections.mysql
```

**Solutions:**

#### Connection Pool Optimization
```bash
# Increase connection limits in .env
DB_CONNECTION_POOL_MAX=50

# MySQL configuration (my.cnf)
max_connections = 200
max_connect_errors = 1000
```

#### Database Performance
```bash
# Check slow queries
mysql -u username -p -e "SHOW VARIABLES LIKE 'slow_query_log%';"

# Optimize DNS tables
php artisan db:optimize
```

### Issue: DNS Table Corruption or Performance

**Symptoms:**
- Slow DNS cache queries
- Inconsistent cache data
- Database errors in logs

**Diagnosis:**
```bash
# Check table status
mysql -u username -p -e "CHECK TABLE dns_lookup_cache, dns_lookup_metrics;"

# Analyze table performance
mysql -u username -p -e "ANALYZE TABLE dns_lookup_cache;"

# Check table sizes
mysql -u username -p -e "SELECT table_name, data_length/1024/1024 as 'Size (MB)' FROM information_schema.tables WHERE table_schema='project_namer';"
```

**Solutions:**

#### Database Maintenance
```bash
# Repair corrupted tables
mysql -u username -p -e "REPAIR TABLE dns_lookup_cache;"

# Optimize tables
mysql -u username -p -e "OPTIMIZE TABLE dns_lookup_cache, dns_lookup_metrics;"

# Clean old data
php artisan dns:cache:cleanup
```

#### Index Optimization
```sql
-- Add missing indexes
CREATE INDEX idx_dns_cache_expires ON dns_lookup_cache(expires_at);
CREATE INDEX idx_dns_cache_domain ON dns_lookup_cache(domain);
CREATE INDEX idx_dns_metrics_created ON dns_lookup_metrics(created_at);
```

## Configuration Problems

### Issue: Environment Configuration Errors

**Symptoms:**
- DNS features not working as expected
- Configuration values not taking effect
- Inconsistent behavior between environments

**Diagnosis:**
```bash
# Check current configuration
php artisan config:show dns

# Verify environment variables
grep DNS_ .env

# Check config cache status
ls -la bootstrap/cache/config.php
```

**Solutions:**

#### Clear Configuration Cache
```bash
# Clear config cache
php artisan config:clear

# Regenerate config cache
php artisan config:cache

# Verify changes took effect
php artisan config:show dns.servers
```

#### Validate Configuration
```bash
# Test configuration values
php artisan dns:config:validate

# Check for typos in .env
grep -E "DNS_[A-Z_]+=" .env | sort
```

### Issue: Service Provider Registration Problems

**Symptoms:**
- DNS services not being injected properly
- "Target class does not exist" errors
- Service resolution failures

**Diagnosis:**
```bash
# Check service registrations
php artisan tinker
>>> app()->getBindings()['App\Services\DnsLookupService'] ?? 'Not found'

# Test service resolution
>>> app('App\Contracts\DnsLookupServiceInterface')
```

**Solutions:**

#### Re-register Service Providers
```bash
# Clear compiled services
php artisan clear-compiled

# Rebuild autoload
composer dump-autoload

# Clear and rebuild all caches
php artisan optimize:clear
php artisan optimize
```

## Monitoring and Logging

### Issue: Missing or Insufficient Logs

**Symptoms:**
- No DNS-related logs appearing
- Missing performance metrics
- Unable to diagnose issues

**Diagnosis:**
```bash
# Check log file permissions
ls -la storage/logs/
ls -la storage/logs/laravel.log

# Test logging functionality
php artisan tinker
>>> Log::info('Test DNS log entry')

# Check log configuration
php artisan config:show logging.channels.stack
```

**Solutions:**

#### Fix Log Permissions
```bash
# Set correct permissions
sudo chown -R www-data:www-data storage/logs/
sudo chmod -R 775 storage/logs/

# Create missing log files
touch storage/logs/laravel.log
sudo chown www-data:www-data storage/logs/laravel.log
```

#### Enable Debug Logging
```bash
# Temporarily enable debug logging
LOG_LEVEL=debug
DNS_LOGGING_LEVEL=debug
DNS_LOGGING_CACHE_OPS=true

php artisan config:cache
```

### Issue: Monitoring Endpoints Not Working

**Symptoms:**
- Health check endpoints returning 404
- Monitoring tools can't access endpoints
- No monitoring data available

**Diagnosis:**
```bash
# Test health endpoints
curl -v http://localhost/health
curl -v http://localhost/health/dns

# Check route registration
php artisan route:list | grep health

# Check web server configuration
sudo nginx -t
```

**Solutions:**

#### Route Registration
```php
// In routes/web.php or routes/monitoring.php
Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/dns', [HealthController::class, 'dns']);
```

#### Web Server Configuration
```nginx
# In nginx configuration
location /health {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Emergency Procedures

### Emergency Recovery Checklist

When the DNS system is completely down:

#### Step 1: Immediate Assessment (0-2 minutes)
```bash
# Quick system status
php artisan dns:health:check
sudo supervisorctl status
curl -s http://localhost/health/dns
```

#### Step 2: Emergency Recovery (2-5 minutes)
```bash
# Emergency DNS recovery
php artisan dns:recover --emergency

# Restart all DNS workers
sudo supervisorctl restart project-namer-dns-worker:*

# Clear all caches
php artisan cache:clear
php artisan dns:cache:clear
```

#### Step 3: Enable Degradation Mode (5-10 minutes)
```bash
# Enable graceful degradation
php artisan dns:degradation:enable --strategy=cache_only

# Or disable DNS filtering temporarily
php artisan dns:degradation:enable --strategy=disabled
```

#### Step 4: Verify Recovery (10-15 minutes)
```bash
# Test basic functionality
php artisan dns:test:basic

# Check queue processing
php artisan queue:monitor dns --timeout=60

# Verify health endpoints
curl -s http://localhost/health/dns | jq .
```

### Escalation Procedures

#### Level 1: Automatic Recovery Failed
- Run emergency recovery commands
- Check recent deployments or changes
- Review error logs for patterns

#### Level 2: Manual Intervention Needed
- Contact senior developer/ops team
- Prepare incident summary
- Consider enabling degradation mode

#### Level 3: Service Impact > 30 minutes
- Notify stakeholders
- Consider temporary DNS filtering disable
- Prepare post-incident review

### Recovery Validation

After any emergency procedure:

```bash
# Validation script
#!/bin/bash
echo "=== DNS Recovery Validation ==="

# Test basic functionality
echo "1. Testing basic DNS functionality..."
php artisan dns:test:basic

# Check health status
echo "2. Checking health status..."
php artisan dns:health:check

# Test performance
echo "3. Testing performance..."
php artisan dns:test:performance --duration=60

# Check queue processing
echo "4. Checking queue processing..."
timeout 30 php artisan queue:monitor dns

# Verify monitoring endpoints
echo "5. Verifying monitoring endpoints..."
curl -s http://localhost/health/dns | jq -r '.overall_status'

echo "=== Recovery validation complete ==="
```

This comprehensive troubleshooting guide provides systematic approaches to diagnosing and resolving DNS filtering system issues. Keep this guide accessible during incidents for quick reference.