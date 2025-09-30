# DNS Monitoring and Alerting Setup Guide

> Version: 1.0.0
> Last Updated: September 29, 2025

## Table of Contents

1. [Monitoring Overview](#monitoring-overview)
2. [Health Check Endpoints](#health-check-endpoints)
3. [Application Monitoring](#application-monitoring)
4. [Performance Metrics](#performance-metrics)
5. [Alert Configuration](#alert-configuration)
6. [External Monitoring Tools](#external-monitoring-tools)
7. [Dashboard Setup](#dashboard-setup)
8. [Log Monitoring](#log-monitoring)
9. [Notification Channels](#notification-channels)
10. [Incident Response](#incident-response)

## Monitoring Overview

The DNS filtering system includes comprehensive monitoring capabilities to ensure high availability, performance, and reliability. This guide covers setting up monitoring, alerting, and observability for all components.

### Monitoring Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Application Layer                            │
├─────────────────────────────────────────────────────────────────┤
│ Health Endpoints │ Performance Metrics │ Business Metrics       │
│ /health/dns     │ Response Time      │ DNS Success Rate      │
│ /health/cache   │ Throughput         │ Domain Checks         │
│ /health/queue   │ Error Rates        │ Cache Hit Rate        │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                  Monitoring Services                            │
├─────────────────────────────────────────────────────────────────┤
│ DnsHealthCheckService │ AlertService     │ MetricsCollector     │
│ PerformanceMonitor   │ CircuitBreaker   │ LoggingService       │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                   Alert Channels                               │
├─────────────────────────────────────────────────────────────────┤
│ Application Logs │ Webhook (Slack)    │ Email Notifications  │
│ External Tools   │ PagerDuty          │ Custom Webhooks      │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                External Monitoring                              │
├─────────────────────────────────────────────────────────────────┤
│ Uptime Monitors  │ APM Tools          │ Log Aggregation      │
│ (UptimeRobot)    │ (New Relic)        │ (ELK Stack)          │
└─────────────────────────────────────────────────────────────────┘
```

## Health Check Endpoints

### 1. Built-in Health Endpoints

The application provides several health check endpoints for monitoring different components:

#### General Application Health
```
GET /health
```

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2025-09-29T12:00:00.000Z",
    "version": "1.0.0",
    "environment": "production"
}
```

#### DNS Service Health
```
GET /health/dns
```

**Response:**
```json
{
    "overall_status": "healthy",
    "last_check": "2025-09-29T12:00:00.000Z",
    "error_rate": {
        "current": 2.5,
        "threshold_warning": 10,
        "threshold_critical": 25,
        "status": "healthy"
    },
    "response_time": {
        "average_ms": 850,
        "p95_ms": 1200,
        "threshold_warning": 2000,
        "threshold_critical": 5000,
        "status": "healthy"
    },
    "cache_hit_rate": {
        "percentage": 85.2,
        "threshold_warning": 70,
        "threshold_critical": 50,
        "status": "healthy"
    },
    "circuit_breaker": {
        "state": "closed",
        "failure_count": 1,
        "last_failure": "2025-09-29T11:45:00.000Z",
        "status": "healthy"
    }
}
```

#### Cache Health
```
GET /health/cache
```

**Response:**
```json
{
    "status": "healthy",
    "cache_driver": "redis",
    "connection_status": "connected",
    "memory_usage": {
        "used_mb": 145,
        "max_mb": 2048,
        "percentage": 7.1
    }
}
```

#### Queue Health
```
GET /health/queue
```

**Response:**
```json
{
    "status": "healthy",
    "dns_queue_size": 12,
    "default_queue_size": 3,
    "worker_status": {
        "running": 4,
        "expected": 4
    }
}
```

### 2. Custom Health Checks

#### Creating Custom Health Checks
```php
// routes/monitoring.php
Route::get('/health/database', function () {
    try {
        DB::select('SELECT 1');
        return response()->json([
            'status' => 'healthy',
            'connection' => 'active',
            'response_time_ms' => DB::getQueryLog()[0]['time'] ?? null
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/health/external-dns', function () {
    $dnsService = app(DnsLookupServiceInterface::class);

    try {
        $startTime = microtime(true);
        $result = $dnsService->checkDomain('google.com');
        $responseTime = (microtime(true) - $startTime) * 1000;

        return response()->json([
            'status' => 'healthy',
            'test_domain' => 'google.com',
            'response_time_ms' => round($responseTime, 2),
            'has_records' => $result->hasRecords
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

## Application Monitoring

### 1. Performance Metrics Collection

#### DNS Performance Metrics
The system automatically collects the following DNS-related metrics:

**Response Time Metrics:**
- Average DNS lookup time
- 95th percentile response time
- Maximum response time
- Timeout frequency

**Success/Failure Metrics:**
- DNS success rate
- Error rate by type
- Circuit breaker state changes
- Fallback server usage

**Cache Metrics:**
- Cache hit rate
- Cache miss rate
- Cache eviction rate
- Cache warming effectiveness

**Queue Metrics:**
- Queue depth
- Job processing time
- Job failure rate
- Worker utilization

#### Accessing Metrics via Artisan Commands

```bash
# Get current DNS health status
php artisan dns:health:check

# Generate performance report
php artisan dns:metrics:report --period=1h

# View detailed metrics
php artisan dns:metrics:detailed --format=json
```

### 2. Custom Metrics Collection

#### Creating Custom Metrics
```php
// In a service provider or middleware
use App\Services\DnsPerformanceMonitorService;

class CustomMetricsCollector
{
    public function __construct(
        private readonly DnsPerformanceMonitorService $performanceMonitor
    ) {}

    public function recordBusinessMetric(string $domain, bool $available): void
    {
        // Record business-specific metrics
        $this->performanceMonitor->recordCustomMetric('domain_availability', [
            'domain' => $domain,
            'available' => $available,
            'timestamp' => now()->toISOString(),
            'tld' => $this->extractTld($domain)
        ]);
    }

    public function recordUserInteraction(string $action, array $context = []): void
    {
        $this->performanceMonitor->recordCustomMetric('user_interaction', [
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id()
        ]);
    }
}
```

## Performance Metrics

### 1. Key Performance Indicators (KPIs)

#### DNS Service KPIs
- **Availability**: Target 99.9% uptime
- **Response Time**: Average < 2 seconds
- **Error Rate**: < 5% of total requests
- **Cache Hit Rate**: > 80% for optimal performance

#### Application KPIs
- **User Satisfaction**: DNS filtering helps > 90% of users
- **System Efficiency**: < 512MB memory per worker
- **Throughput**: Handle > 1000 domains/hour per worker

### 2. Monitoring Thresholds

#### Warning Thresholds
```bash
# Environment variables for warning thresholds
DNS_ALERT_ERROR_RATE_THRESHOLD=10.0          # 10% error rate
DNS_ALERT_RESPONSE_TIME_THRESHOLD=3000.0     # 3 seconds
DNS_ALERT_CACHE_HIT_RATE_THRESHOLD=70.0      # 70% cache hit rate
DNS_ALERT_QUEUE_DEPTH_THRESHOLD=50           # 50 jobs in queue
```

#### Critical Thresholds
```bash
# Critical thresholds that require immediate action
DNS_CRITICAL_ERROR_RATE=25.0                 # 25% error rate
DNS_CRITICAL_RESPONSE_TIME=5000.0             # 5 seconds
DNS_CRITICAL_CACHE_HIT_RATE=50.0              # 50% cache hit rate
DNS_CRITICAL_QUEUE_DEPTH=100                  # 100 jobs in queue
DNS_CRITICAL_CB_FAILURES=5                    # Circuit breaker failures
```

### 3. Performance Benchmarking

#### Baseline Performance Test
```bash
# Create performance baseline script
#!/bin/bash
# scripts/performance-baseline.sh

echo "=== DNS Performance Baseline Test ==="
echo "Date: $(date)"
echo "Environment: $APP_ENV"
echo ""

# Test DNS lookup performance
echo "Testing DNS lookup performance..."
php artisan dns:test:performance --duration=300 --concurrent=5

# Test cache performance
echo ""
echo "Testing cache performance..."
php artisan dns:cache:benchmark --operations=1000

# Test queue performance
echo ""
echo "Testing queue performance..."
php artisan queue:monitor dns --timeout=60

# System resource usage
echo ""
echo "System Resources:"
echo "Memory Usage: $(free -h | awk '/^Mem/ {print $3 "/" $2}')"
echo "CPU Usage: $(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)%"
echo "Disk Usage: $(df -h / | awk '/\// {print $5}')"

echo ""
echo "=== Baseline Test Complete ==="
```

## Alert Configuration

### 1. Built-in Alert System

#### Basic Alert Configuration
```bash
# Enable DNS alerts
DNS_ALERTS_ENABLED=true

# Configure alert suppression (prevent spam)
DNS_ALERTS_SUPPRESSION_WINDOW=60  # minutes

# Configure notification channels
DNS_ALERTS_LOG_ENABLED=true
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_ALERTS_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

#### Alert Threshold Configuration
```php
// config/dns.php
'alerts' => [
    'enabled' => env('DNS_ALERTS_ENABLED', true),
    'suppression_window' => (int) env('DNS_ALERTS_SUPPRESSION_WINDOW', 60),

    'thresholds' => [
        // Warning levels (investigate but not urgent)
        'error_rate_warning' => 10.0,          // 10% error rate
        'response_time_warning' => 3000.0,     // 3 seconds
        'cache_hit_rate_warning' => 70.0,      // 70% cache hit rate

        // Critical levels (require immediate action)
        'error_rate_critical' => 25.0,         // 25% error rate
        'response_time_critical' => 5000.0,    // 5 seconds
        'cache_hit_rate_critical' => 50.0,     // 50% cache hit rate
        'circuit_breaker_failures' => 5,       // Circuit breaker trips
    ],

    'notifications' => [
        'log_enabled' => true,
        'webhook_enabled' => true,
        'webhook_url' => env('DNS_ALERTS_WEBHOOK_URL'),
        'email_enabled' => false,  // Configure SMTP to enable
    ],
],
```

### 2. Custom Alert Rules

#### Creating Custom Alert Conditions
```php
// app/Services/CustomAlertService.php
class CustomAlertService
{
    public function checkBusinessRules(): void
    {
        // Custom business logic alerts
        $this->checkDomainAvailabilityTrends();
        $this->checkUserEngagementMetrics();
        $this->checkResourceUtilization();
    }

    private function checkDomainAvailabilityTrends(): void
    {
        $availabilityRate = $this->calculateDomainAvailabilityRate();

        if ($availabilityRate < 20.0) {
            $this->sendAlert('low_domain_availability', [
                'availability_rate' => $availabilityRate,
                'threshold' => 20.0,
                'message' => 'Domain availability rate is unusually low'
            ]);
        }
    }

    private function checkUserEngagementMetrics(): void
    {
        $rejectionRate = $this->calculateSuggestionRejectionRate();

        if ($rejectionRate > 80.0) {
            $this->sendAlert('high_rejection_rate', [
                'rejection_rate' => $rejectionRate,
                'threshold' => 80.0,
                'message' => 'Users rejecting most domain suggestions'
            ]);
        }
    }
}
```

#### Scheduling Custom Alert Checks
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Run business rules checks every 15 minutes
    $schedule->call([app(CustomAlertService::class), 'checkBusinessRules'])
        ->everyFifteenMinutes()
        ->withoutOverlapping();

    // Daily summary reports
    $schedule->command('dns:alerts:summary')
        ->dailyAt('09:00')
        ->emailOutputTo('ops@yourcompany.com');
}
```

## External Monitoring Tools

### 1. Uptime Monitoring

#### UptimeRobot Configuration
```bash
# Health check URLs to monitor
https://your-domain.com/health
https://your-domain.com/health/dns
https://your-domain.com/health/cache
https://your-domain.com/health/queue

# Monitor every 5 minutes
# Alert if down for > 2 minutes
# Check from multiple locations
```

#### Pingdom Setup
```javascript
// Pingdom transaction check example
var page = require('webpage').create();

page.open('https://your-domain.com/health/dns', function(status) {
    if (status === 'success') {
        var content = page.evaluate(function() {
            return document.body.textContent;
        });

        var health = JSON.parse(content);

        if (health.overall_status === 'healthy') {
            console.log('DNS service is healthy');
        } else {
            console.error('DNS service is not healthy: ' + health.overall_status);
        }
    } else {
        console.error('Failed to load health check page');
    }

    phantom.exit();
});
```

### 2. Application Performance Monitoring (APM)

#### New Relic Configuration
```bash
# Install New Relic PHP agent
curl -Ls https://download.newrelic.com/php_agent/release/newrelic-php5-10.x.x.x-linux.tar.gz | tar -C /tmp -zx
cd /tmp/newrelic-php5-10.x.x.x-linux
sudo ./newrelic-install install

# Configure in .env
NEWRELIC_ENABLED=true
NEWRELIC_APPNAME="Project Namer DNS"
NEWRELIC_LICENSE="your-license-key"
```

#### DataDog Configuration
```yaml
# datadog.yaml
api_key: "your-api-key"
app_key: "your-app-key"

logs_enabled: true
log_level: INFO

apm_config:
  enabled: true
  env: production
  service: project-namer

integrations:
  php:
    service_name: project-namer-dns
    enabled: true

  mysql:
    host: localhost
    port: 3306
    username: monitoring_user
    password: monitoring_password

  redis:
    host: localhost
    port: 6379
```

### 3. Infrastructure Monitoring

#### Prometheus Configuration
```yaml
# prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - "dns_alerts.yml"

scrape_configs:
  - job_name: 'project-namer'
    static_configs:
      - targets: ['localhost:9090']
    metrics_path: '/metrics'
    scrape_interval: 30s

  - job_name: 'dns-health'
    static_configs:
      - targets: ['your-domain.com:443']
    metrics_path: '/health/dns'
    scrape_interval: 60s
    scheme: https

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093
```

#### Grafana Dashboard Configuration
```json
{
  "dashboard": {
    "title": "DNS Service Monitoring",
    "panels": [
      {
        "title": "DNS Response Time",
        "type": "graph",
        "targets": [
          {
            "expr": "dns_response_time_seconds",
            "legendFormat": "Average Response Time"
          }
        ],
        "yAxes": [
          {
            "label": "Seconds",
            "min": 0
          }
        ]
      },
      {
        "title": "Error Rate",
        "type": "stat",
        "targets": [
          {
            "expr": "rate(dns_errors_total[5m]) * 100",
            "legendFormat": "Error Rate %"
          }
        ],
        "thresholds": [
          {"color": "green", "value": 0},
          {"color": "yellow", "value": 5},
          {"color": "red", "value": 10}
        ]
      }
    ]
  }
}
```

## Dashboard Setup

### 1. Application Dashboard

#### Laravel Telescope Integration
```php
// config/telescope.php
'watchers' => [
    Watchers\CacheWatcher::class => [
        'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
    ],
    Watchers\CommandWatcher::class => [
        'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
        'ignore' => ['schedule:run'],
    ],
    Watchers\JobWatcher::class => [
        'enabled' => env('TELESCOPE_JOB_WATCHER', true),
    ],
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'ignore_packages' => true,
        'slow' => 100, // milliseconds
    ],
],
```

#### Custom Dashboard Endpoints
```php
// routes/monitoring.php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard/dns', function () {
        $healthService = app(DnsHealthCheckService::class);
        $performanceMonitor = app(DnsPerformanceMonitorInterface::class);

        return view('monitoring.dns-dashboard', [
            'health_status' => $healthService->getHealthStatus(),
            'performance_stats' => $performanceMonitor->getAggregatedStats(60),
            'recent_metrics' => DnsLookupMetrics::latest()->limit(10)->get(),
        ]);
    });

    Route::get('/dashboard/dns/api', function () {
        return response()->json([
            'health' => app(DnsHealthCheckService::class)->getHealthStatus(),
            'performance' => app(DnsPerformanceMonitorInterface::class)->getAggregatedStats(60),
            'queue_status' => [
                'dns_queue_size' => Queue::size('dns'),
                'default_queue_size' => Queue::size(),
            ],
        ]);
    });
});
```

### 2. External Dashboard Tools

#### Grafana Dashboard JSON
```json
{
  "dashboard": {
    "id": null,
    "title": "Project Namer DNS Monitoring",
    "tags": ["dns", "project-namer"],
    "timezone": "UTC",
    "panels": [
      {
        "id": 1,
        "title": "DNS Health Overview",
        "type": "stat",
        "targets": [
          {
            "expr": "dns_health_status",
            "refId": "A"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "mappings": [
              {"value": 0, "text": "Unhealthy", "color": "red"},
              {"value": 1, "text": "Warning", "color": "yellow"},
              {"value": 2, "text": "Healthy", "color": "green"}
            ]
          }
        }
      },
      {
        "id": 2,
        "title": "DNS Response Time Trend",
        "type": "timeseries",
        "targets": [
          {
            "expr": "avg(dns_response_time_seconds) by (instance)",
            "refId": "A",
            "legendFormat": "Average Response Time"
          },
          {
            "expr": "quantile(0.95, dns_response_time_seconds) by (instance)",
            "refId": "B",
            "legendFormat": "95th Percentile"
          }
        ]
      },
      {
        "id": 3,
        "title": "Error Rate",
        "type": "timeseries",
        "targets": [
          {
            "expr": "rate(dns_errors_total[5m]) * 100",
            "refId": "A",
            "legendFormat": "Error Rate %"
          }
        ],
        "alert": {
          "conditions": [
            {
              "query": {"queryType": "A", "refId": "A"},
              "reducer": {"params": [], "type": "last"},
              "evaluator": {"params": [10], "type": "gt"}
            }
          ],
          "executionErrorState": "alerting",
          "for": "5m",
          "frequency": "10s",
          "handler": 1,
          "name": "High DNS Error Rate",
          "noDataState": "no_data"
        }
      }
    ]
  }
}
```

## Log Monitoring

### 1. Application Log Monitoring

#### Structured Logging Configuration
```php
// config/logging.php
'channels' => [
    'dns' => [
        'driver' => 'daily',
        'path' => storage_path('logs/dns.log'),
        'level' => 'info',
        'days' => 14,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],

    'dns_performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/dns-performance.log'),
        'level' => 'info',
        'days' => 7,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],

    'dns_errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/dns-errors.log'),
        'level' => 'error',
        'days' => 30,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
],
```

#### Log Aggregation with ELK Stack

##### Filebeat Configuration
```yaml
# filebeat.yml
filebeat.inputs:
- type: log
  enabled: true
  paths:
    - /var/www/project-namer/storage/logs/dns*.log
  fields:
    service: dns
    environment: production
  fields_under_root: true
  json.keys_under_root: true
  json.add_error_key: true

output.elasticsearch:
  hosts: ["elasticsearch:9200"]
  index: "project-namer-dns-%{+yyyy.MM.dd}"

processors:
- add_host_metadata:
    when.not.contains.tags: forwarded
```

##### Logstash Configuration
```ruby
# logstash.conf
input {
  beats {
    port => 5044
  }
}

filter {
  if [service] == "dns" {
    mutate {
      add_tag => [ "dns-service" ]
    }

    # Parse DNS performance metrics
    if [message] =~ /DNS.*completed/ {
      grok {
        match => {
          "message" => "DNS.*completed.*processing_time_ms.*%{NUMBER:processing_time:float}"
        }
      }
      mutate {
        add_tag => [ "dns-performance" ]
      }
    }

    # Parse DNS errors
    if [level] == "error" {
      mutate {
        add_tag => [ "dns-error" ]
      }
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "project-namer-%{service}-%{+YYYY.MM.dd}"
  }
}
```

### 2. Log Analysis and Alerting

#### Elasticsearch Queries for Monitoring
```json
# High error rate query
{
  "query": {
    "bool": {
      "must": [
        {"match": {"service": "dns"}},
        {"match": {"level": "error"}},
        {"range": {"@timestamp": {"gte": "now-5m"}}}
      ]
    }
  },
  "aggs": {
    "error_count": {
      "cardinality": {
        "field": "@timestamp"
      }
    }
  }
}

# Slow DNS queries
{
  "query": {
    "bool": {
      "must": [
        {"match": {"service": "dns"}},
        {"range": {"processing_time": {"gte": 3000}}}
      ]
    }
  },
  "sort": [
    {"@timestamp": {"order": "desc"}}
  ]
}
```

#### Kibana Alerts Configuration
```json
{
  "name": "High DNS Error Rate",
  "consumer": "alerts",
  "enabled": true,
  "schedule": {
    "interval": "1m"
  },
  "params": {
    "index": ["project-namer-dns-*"],
    "timeField": "@timestamp",
    "aggType": "count",
    "termSize": 5,
    "termField": "level.keyword",
    "thresholdComparator": ">",
    "threshold": [10],
    "timeWindowSize": 5,
    "timeWindowUnit": "m"
  },
  "actions": [
    {
      "id": "slack-webhook",
      "group": "threshold met",
      "params": {
        "message": "High DNS error rate detected: {{context.value}} errors in the last 5 minutes"
      }
    }
  ]
}
```

## Notification Channels

### 1. Slack Integration

#### Webhook Configuration
```php
// config/dns.php
'alerts' => [
    'notifications' => [
        'webhook_enabled' => true,
        'webhook_url' => env('DNS_ALERTS_WEBHOOK_URL'),
        'webhook_channel' => '#ops-alerts',
        'webhook_username' => 'DNS Monitor',
        'webhook_icon' => ':warning:',
    ],
],
```

#### Custom Slack Notifications
```php
// app/Services/SlackNotificationService.php
class SlackNotificationService
{
    public function sendDnsAlert(string $type, array $data): void
    {
        $webhook = config('dns.alerts.notifications.webhook_url');

        $payload = [
            'channel' => '#ops-alerts',
            'username' => 'DNS Monitor',
            'icon_emoji' => $this->getIconForAlertType($type),
            'attachments' => [
                [
                    'color' => $this->getColorForAlertType($type),
                    'title' => "DNS Alert: " . ucwords(str_replace('_', ' ', $type)),
                    'fields' => $this->formatAlertFields($data),
                    'ts' => time(),
                ]
            ]
        ];

        Http::post($webhook, $payload);
    }

    private function formatAlertFields(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            $fields[] = [
                'title' => ucwords(str_replace('_', ' ', $key)),
                'value' => is_numeric($value) ? number_format($value, 2) : $value,
                'short' => true,
            ];
        }

        return $fields;
    }
}
```

### 2. Email Notifications

#### SMTP Configuration
```bash
# .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="DNS Monitor"

# Enable email alerts
DNS_ALERTS_EMAIL_ENABLED=true
DNS_ALERTS_EMAIL_RECIPIENTS="ops@yourcompany.com,admin@yourcompany.com"
```

#### Email Alert Templates
```php
// app/Mail/DnsAlertMail.php
class DnsAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $alertType,
        private readonly array $alertData
    ) {}

    public function build(): self
    {
        $severity = $this->getSeverity($this->alertData);

        return $this->subject("DNS Alert: {$this->alertType} ({$severity})")
            ->markdown('emails.dns-alert')
            ->with([
                'alertType' => $this->alertType,
                'alertData' => $this->alertData,
                'severity' => $severity,
                'timestamp' => now()->format('Y-m-d H:i:s T'),
            ]);
    }
}
```

### 3. PagerDuty Integration

#### PagerDuty Configuration
```php
// config/services.php
'pagerduty' => [
    'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
    'api_endpoint' => 'https://events.pagerduty.com/v2/enqueue',
],
```

#### PagerDuty Alert Service
```php
// app/Services/PagerDutyService.php
class PagerDutyService
{
    public function createIncident(string $summary, array $details, string $severity = 'error'): void
    {
        $payload = [
            'routing_key' => config('services.pagerduty.integration_key'),
            'event_action' => 'trigger',
            'dedup_key' => 'dns-alert-' . md5($summary),
            'payload' => [
                'summary' => $summary,
                'severity' => $severity,
                'source' => 'project-namer-dns',
                'component' => 'dns-service',
                'group' => 'infrastructure',
                'class' => 'dns',
                'custom_details' => $details,
            ],
        ];

        Http::post(config('services.pagerduty.api_endpoint'), $payload);
    }

    public function resolveIncident(string $dedupKey): void
    {
        $payload = [
            'routing_key' => config('services.pagerduty.integration_key'),
            'event_action' => 'resolve',
            'dedup_key' => $dedupKey,
        ];

        Http::post(config('services.pagerduty.api_endpoint'), $payload);
    }
}
```

## Incident Response

### 1. Incident Response Playbooks

#### DNS Service Down Playbook
```markdown
# DNS Service Down - Response Playbook

## Immediate Actions (0-5 minutes)
1. **Acknowledge Alert**: Confirm receipt and ownership
2. **Check Service Status**:
   - Visit /health/dns endpoint
   - Check DNS worker processes: `sudo supervisorctl status`
   - Verify external DNS servers: `nslookup google.com 8.8.8.8`

## Investigation (5-15 minutes)
1. **Check Logs**:
   ```bash
   tail -100 /var/www/project-namer/storage/logs/laravel.log
   tail -50 /var/www/project-namer/storage/logs/dns-worker.log
   ```

2. **Check System Resources**:
   ```bash
   free -h
   df -h
   top -n1
   ```

3. **Check External Dependencies**:
   - Database connectivity: `mysql -u username -p -h host -e "SELECT 1"`
   - Redis connectivity: `redis-cli ping`

## Resolution Steps
1. **Restart DNS Workers**: `sudo supervisorctl restart project-namer-dns-worker:*`
2. **Clear Caches**: `php artisan cache:clear && php artisan dns:cache:clear`
3. **Reset Circuit Breaker**: `php artisan dns:recover --emergency`
4. **Test Recovery**: `php artisan dns:test:basic`

## Escalation
- If issue persists > 15 minutes: Escalate to senior engineer
- If affecting > 50% of users: Page infrastructure team
```

#### High Error Rate Playbook
```markdown
# High DNS Error Rate - Response Playbook

## Investigation Steps
1. **Check Error Types**:
   ```bash
   grep -i error /var/www/project-namer/storage/logs/dns-*.log | tail -20
   ```

2. **Check DNS Server Health**:
   ```bash
   php artisan dns:servers:test
   ```

3. **Review Circuit Breaker Status**:
   ```bash
   php artisan dns:health:check --detailed
   ```

## Resolution Actions
1. **Enable Degradation Mode**:
   ```bash
   php artisan dns:degradation:enable --strategy=cache_only
   ```

2. **Switch to Fallback DNS**:
   ```bash
   # Update .env or config
   DNS_SERVERS=1.1.1.1,8.8.8.8
   php artisan config:cache
   ```

3. **Warm Cache**:
   ```bash
   php artisan dns:cache:warm --popular
   ```
```

### 2. Automated Incident Response

#### Auto-Recovery Scripts
```php
// app/Console/Commands/DnsAutoRecoveryCommand.php
class DnsAutoRecoveryCommand extends Command
{
    protected $signature = 'dns:auto-recovery';

    public function handle(
        DnsHealthCheckService $healthCheck,
        DnsRecoveryService $recovery
    ): int {
        $health = $healthCheck->getHealthStatus();

        if ($health['overall_status'] === 'critical') {
            $this->info('Critical DNS health detected, initiating auto-recovery...');

            // Attempt automatic recovery
            $result = $recovery->executeRecovery();

            if ($result['success']) {
                $this->info('Auto-recovery completed successfully');

                // Send notification
                $this->notifyRecovery($result);

                return Command::SUCCESS;
            } else {
                $this->error('Auto-recovery failed, manual intervention required');

                // Page operations team
                $this->pageOperationsTeam($result);

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
```

#### Automated Recovery Scheduling
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Auto-recovery check every 5 minutes during critical periods
    $schedule->command('dns:auto-recovery')
        ->everyFiveMinutes()
        ->between('06:00', '23:00')  // Business hours
        ->withoutOverlapping();

    // Less frequent checks during off-hours
    $schedule->command('dns:auto-recovery')
        ->hourly()
        ->between('23:01', '05:59')  // Off hours
        ->withoutOverlapping();
}
```

This comprehensive monitoring and alerting guide provides everything needed to maintain visibility into the DNS filtering system's health and performance. Regular monitoring and proactive alerting help ensure high availability and optimal user experience.