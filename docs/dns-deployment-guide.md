# DNS Service Deployment Guide

> Version: 1.0.0
> Last Updated: September 29, 2025

## Table of Contents

1. [Deployment Overview](#deployment-overview)
2. [Prerequisites](#prerequisites)
3. [Environment Setup](#environment-setup)
4. [Database Migration](#database-migration)
5. [Service Configuration](#service-configuration)
6. [Queue Worker Setup](#queue-worker-setup)
7. [Monitoring Setup](#monitoring-setup)
8. [Load Balancer Configuration](#load-balancer-configuration)
9. [Security Configuration](#security-configuration)
10. [Performance Optimization](#performance-optimization)
11. [Docker Deployment](#docker-deployment)
12. [Cloud Deployment](#cloud-deployment)
13. [Rollback Procedures](#rollback-procedures)
14. [Post-Deployment Verification](#post-deployment-verification)

## Deployment Overview

The DNS filtering system requires careful deployment to ensure high availability, performance, and reliability. This guide covers deployment strategies for various environments and platforms.

### Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Load Balancer                              │
│                    (HAProxy/Nginx)                             │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                   Application Servers                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   App 1     │  │   App 2     │  │   App 3     │             │
│  │ (Laravel)   │  │ (Laravel)   │  │ (Laravel)   │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                    Queue Workers                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ DNS Worker 1│  │ DNS Worker 2│  │ DNS Worker 3│             │
│  │ (4 processes│  │ (4 processes│  │ (4 processes│             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                    Data Layer                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   MySQL     │  │    Redis    │  │   File      │             │
│  │ (Primary +  │  │  (Cache +   │  │  Storage    │             │
│  │  Replica)   │  │   Queue)    │  │  (Logs)     │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
```

## Prerequisites

### System Requirements

#### Minimum Requirements (Development)
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB SSD
- **Network**: 100Mbps
- **OS**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+

#### Recommended Requirements (Production)
- **CPU**: 4+ cores per application server
- **RAM**: 8GB+ per application server
- **Storage**: 100GB+ SSD with IOPS 3000+
- **Network**: 1Gbps+ with redundancy
- **OS**: Ubuntu 22.04 LTS (recommended)

#### High-Traffic Requirements (Enterprise)
- **CPU**: 8+ cores per server
- **RAM**: 16GB+ per application server
- **Storage**: 500GB+ NVMe SSD
- **Network**: 10Gbps+ with redundancy
- **Load Balancer**: Dedicated hardware/cloud LB

### Software Dependencies

#### Core Requirements
```bash
# PHP and Extensions
PHP 8.4+ with extensions:
- pdo_mysql
- redis
- curl
- json
- mbstring
- xml
- zip
- bcmath

# Database
MySQL 8.0+ or MariaDB 10.6+

# Cache and Queue
Redis 6.0+

# Web Server
Nginx 1.20+ or Apache 2.4+

# Process Manager
Supervisor or systemd
```

#### Optional but Recommended
```bash
# Monitoring
Prometheus + Grafana
New Relic / DataDog

# Log Management
ELK Stack (Elasticsearch, Logstash, Kibana)
Fluentd / Fluent Bit

# Container Platform (if using Docker)
Docker 20.10+
Docker Compose 2.0+
Kubernetes 1.25+ (for large deployments)
```

## Environment Setup

### 1. Server Preparation

#### Ubuntu/Debian Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    nginx \
    mysql-server \
    redis-server \
    supervisor \
    git \
    curl \
    unzip

# Install PHP 8.4
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y \
    php8.4 \
    php8.4-fpm \
    php8.4-mysql \
    php8.4-redis \
    php8.4-curl \
    php8.4-json \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-zip \
    php8.4-bcmath \
    php8.4-cli
```

#### CentOS/RHEL Setup
```bash
# Update system
sudo yum update -y

# Install EPEL and Remi repositories
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.4
sudo dnf module enable php:remi-8.4 -y

# Install required packages
sudo yum install -y \
    nginx \
    mysql-server \
    redis \
    supervisor \
    git \
    curl \
    unzip \
    php \
    php-fpm \
    php-mysql \
    php-redis \
    php-json \
    php-mbstring \
    php-xml \
    php-zip \
    php-bcmath
```

### 2. Application Deployment

#### Clone Repository
```bash
# Create application directory
sudo mkdir -p /var/www/project-namer
sudo chown $USER:www-data /var/www/project-namer

# Clone repository
git clone https://github.com/your-repo/project-namer.git /var/www/project-namer
cd /var/www/project-namer

# Set proper permissions
sudo chown -R www-data:www-data /var/www/project-namer
sudo chmod -R 755 /var/www/project-namer
sudo chmod -R 775 /var/www/project-namer/storage
sudo chmod -R 775 /var/www/project-namer/bootstrap/cache
```

#### Install Dependencies
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js and npm dependencies
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
npm install
npm run build
```

## Database Migration

### 1. Database Setup

#### Create Database and User
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE project_namer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'project_namer'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant permissions
GRANT ALL PRIVILEGES ON project_namer.* TO 'project_namer'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 2. Run Migrations

```bash
# Copy environment file
cp .env.example .env

# Configure database connection in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_namer
DB_USERNAME=project_namer
DB_PASSWORD=secure_password_here

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed initial data (if needed)
php artisan db:seed --class=DnsConfigurationSeeder
```

### 3. Verify DNS Tables

```bash
# Check DNS-specific tables
php artisan tinker

# In Tinker shell:
DB::select('SHOW TABLES LIKE "dns_%"');

# Expected tables:
# - dns_lookup_cache
# - dns_lookup_metrics

# Check name_suggestions table has DNS fields
DB::select('DESCRIBE name_suggestions');

# Expected DNS fields:
# - dns_checked
# - dns_has_records
# - dns_checked_at
```

## Service Configuration

### 1. Environment Configuration

#### Production .env File
```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_namer
DB_USERNAME=project_namer
DB_PASSWORD=secure_password_here

# Cache and Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password_here
REDIS_PORT=6379

# DNS Configuration
DNS_SERVERS=8.8.8.8,8.8.4.4,1.1.1.1,1.0.0.1
DNS_TIMEOUT=2
DNS_CACHE_TTL=86400
DNS_BATCH_SIZE=15
DNS_CONCURRENT_LOOKUPS=8

# DNS Circuit Breaker
DNS_CB_ENABLED=true
DNS_CB_FAILURE_THRESHOLD=5
DNS_CB_TIMEOUT_MINUTES=5

# DNS Monitoring
DNS_ALERTS_ENABLED=true
DNS_ALERTS_WEBHOOK_ENABLED=true
DNS_ALERTS_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# DNS Logging
DNS_LOGGING_ENABLED=true
DNS_LOGGING_LEVEL=warning
DNS_LOGGING_PERFORMANCE=true

# DNS Cache Warming
DNS_WARMING_ENABLED=true
DNS_WARMING_RATE_LIMIT=500
```

### 2. Cache Configuration

```bash
# Clear and optimize caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Queue Worker Setup

### 1. Supervisor Configuration

#### Create DNS Worker Configuration
```bash
# Create supervisor config for DNS workers
sudo nano /etc/supervisor/conf.d/project-namer-dns-worker.conf
```

```ini
[program:project-namer-dns-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/project-namer/artisan queue:work --queue=dns --sleep=3 --tries=3 --timeout=30 --memory=512
directory=/var/www/project-namer
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/project-namer/storage/logs/dns-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

#### Create General Worker Configuration
```bash
sudo nano /etc/supervisor/conf.d/project-namer-worker.conf
```

```ini
[program:project-namer-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/project-namer/artisan queue:work --sleep=3 --tries=3 --timeout=30 --memory=256
directory=/var/www/project-namer
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/project-namer/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

#### Start Supervisor Services
```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start project-namer-dns-worker:*
sudo supervisorctl start project-namer-worker:*

# Check worker status
sudo supervisorctl status
```

### 2. Systemd Configuration (Alternative)

#### Create DNS Worker Service
```bash
sudo nano /etc/systemd/system/project-namer-dns-worker@.service
```

```ini
[Unit]
Description=Project Namer DNS Worker %i
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/project-namer/artisan queue:work --queue=dns --sleep=3 --tries=3 --timeout=30 --memory=512
WorkingDirectory=/var/www/project-namer
StandardOutput=append:/var/www/project-namer/storage/logs/dns-worker-%i.log
StandardError=append:/var/www/project-namer/storage/logs/dns-worker-%i.log

[Install]
WantedBy=multi-user.target
```

#### Start Multiple Worker Instances
```bash
# Enable and start 4 DNS workers
for i in {1..4}; do
    sudo systemctl enable project-namer-dns-worker@$i
    sudo systemctl start project-namer-dns-worker@$i
done

# Check worker status
systemctl status project-namer-dns-worker@*
```

### 3. Laravel Scheduler

#### Add Cron Job for Laravel Scheduler
```bash
# Edit crontab
sudo crontab -e -u www-data

# Add Laravel scheduler
* * * * * cd /var/www/project-namer && php artisan schedule:run >> /dev/null 2>&1
```

#### Schedule DNS Maintenance Tasks
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // DNS cache cleanup (daily)
    $schedule->command('dns:cache:cleanup')
        ->dailyAt('02:00')
        ->withoutOverlapping();

    // DNS health monitoring (every 5 minutes)
    $schedule->command('dns:health:monitor')
        ->everyFiveMinutes()
        ->withoutOverlapping();

    // DNS metrics report (hourly)
    $schedule->command('dns:metrics:report')
        ->hourly()
        ->withoutOverlapping();

    // DNS cache warming (every 4 hours)
    $schedule->command('dns:cache:warm')
        ->cron('0 */4 * * *')
        ->withoutOverlapping();
}
```

## Monitoring Setup

### 1. Application Monitoring

#### Health Check Endpoints
```bash
# Add to routes/web.php or create dedicated routes/monitoring.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version')
    ]);
});

Route::get('/health/dns', function () {
    $dnsHealth = app('App\Services\DnsHealthCheckService');
    return response()->json($dnsHealth->getHealthStatus());
});

Route::get('/health/cache', function () {
    try {
        Cache::put('health_check', 'ok', 60);
        $cached = Cache::get('health_check');

        return response()->json([
            'status' => $cached === 'ok' ? 'healthy' : 'unhealthy',
            'cache_driver' => config('cache.default')
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/health/queue', function () {
    try {
        $queueSize = Queue::size('dns');

        return response()->json([
            'status' => $queueSize < 100 ? 'healthy' : 'warning',
            'dns_queue_size' => $queueSize,
            'default_queue_size' => Queue::size()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

### 2. Log Monitoring

#### Configure Log Rotation
```bash
# Create logrotate config
sudo nano /etc/logrotate.d/project-namer
```

```
/var/www/project-namer/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /bin/systemctl reload php8.4-fpm.service > /dev/null 2>&1 || true
    endscript
}
```

#### DNS-Specific Log Monitoring
```bash
# Monitor DNS errors
tail -f /var/www/project-namer/storage/logs/laravel.log | grep -i "dns"

# Monitor queue worker logs
tail -f /var/www/project-namer/storage/logs/dns-worker.log

# Monitor DNS performance
tail -f /var/www/project-namer/storage/logs/laravel.log | grep "DNS.*completed"
```

### 3. External Monitoring

#### Uptime Monitoring
```bash
# Example monitoring script
#!/bin/bash
# Save as /usr/local/bin/dns-health-check.sh

URL="https://your-domain.com/health/dns"
WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

response=$(curl -s -w "%{http_code}" "$URL")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" != "200" ]; then
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"DNS Health Check Failed: HTTP $http_code\"}" \
        "$WEBHOOK"
fi
```

```bash
# Make script executable
chmod +x /usr/local/bin/dns-health-check.sh

# Add to crontab (check every 5 minutes)
echo "*/5 * * * * /usr/local/bin/dns-health-check.sh" | sudo crontab -
```

## Load Balancer Configuration

### 1. Nginx Load Balancer

#### Create Upstream Configuration
```nginx
# /etc/nginx/sites-available/project-namer
upstream project_namer_app {
    least_conn;
    server 10.0.1.10:80 max_fails=3 fail_timeout=30s;
    server 10.0.1.11:80 max_fails=3 fail_timeout=30s;
    server 10.0.1.12:80 max_fails=3 fail_timeout=30s;
}

server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self'" always;

    # Health Check Endpoint
    location /health {
        access_log off;
        proxy_pass http://project_namer_app;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Main Application
    location / {
        proxy_pass http://project_namer_app;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;

        # Timeout settings
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
    }

    # Static Files (optional, if serving from load balancer)
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
        proxy_pass http://project_namer_app;
    }
}
```

### 2. HAProxy Configuration

#### Basic HAProxy Setup
```
# /etc/haproxy/haproxy.cfg
global
    daemon
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy

defaults
    mode http
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms
    option httplog
    option dontlognull
    option http-server-close
    option forwardfor
    option redispatch
    retries 3

frontend project_namer_frontend
    bind *:80
    bind *:443 ssl crt /path/to/ssl/certificate.pem
    redirect scheme https if !{ ssl_fc }

    # Health check
    acl health_check path_beg /health
    use_backend health_backend if health_check

    default_backend project_namer_backend

backend project_namer_backend
    balance roundrobin
    option httpchk GET /health

    server app1 10.0.1.10:80 check maxconn 100
    server app2 10.0.1.11:80 check maxconn 100
    server app3 10.0.1.12:80 check maxconn 100

backend health_backend
    server app1 10.0.1.10:80 check
    server app2 10.0.1.11:80 check
    server app3 10.0.1.12:80 check

listen stats
    bind *:8080
    stats enable
    stats uri /stats
    stats refresh 10s
    stats admin if TRUE
```

## Security Configuration

### 1. Application Security

#### Environment Variables Security
```bash
# Set proper permissions for .env file
chmod 600 .env
chown www-data:www-data .env

# Ensure sensitive files are not accessible
echo "deny all;" | sudo tee /etc/nginx/snippets/deny-sensitive.conf

# Add to server block
include snippets/deny-sensitive.conf;

location ~ /\. {
    deny all;
}

location ~ \.(env|log)$ {
    deny all;
}
```

#### Rate Limiting
```nginx
# Add to nginx server block
# Rate limiting for DNS endpoints
location ~* ^/api/(dns|domain) {
    limit_req zone=api burst=20 nodelay;
    limit_req zone=ip burst=50 nodelay;

    proxy_pass http://project_namer_app;
    # ... other proxy settings
}

# Rate limiting zones (add to http block)
limit_req_zone $binary_remote_addr zone=ip:10m rate=100r/m;
limit_req_zone $request_uri zone=api:10m rate=50r/m;
```

### 2. DNS Security

#### Input Validation
```php
// Already implemented in DnsLookupService
private function validateDomain(string $domain): bool
{
    // Length check
    if (strlen($domain) > 253) {
        return false;
    }

    // Character validation
    if (!preg_match('/^[a-z0-9.-]+$/i', $domain)) {
        return false;
    }

    // Structure validation
    return filter_var('http://' . $domain, FILTER_VALIDATE_URL) !== false;
}
```

#### DNS Query Security
```bash
# Environment variables for security
DNS_LOGGING_SECURITY=true
DNS_VALIDATION_STRICT=true
DNS_RATE_LIMITING=true
```

### 3. Network Security

#### Firewall Configuration
```bash
# UFW (Ubuntu Firewall)
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH
sudo ufw allow ssh

# Allow HTTP/HTTPS
sudo ufw allow 'Nginx Full'

# Allow specific ports for monitoring
sudo ufw allow 8080  # HAProxy stats
sudo ufw allow 9090  # Prometheus (if used)

# Allow internal network for database/Redis
sudo ufw allow from 10.0.0.0/8 to any port 3306
sudo ufw allow from 10.0.0.0/8 to any port 6379

# Deny external access to sensitive ports
sudo ufw deny 3306
sudo ufw deny 6379
sudo ufw deny 11211  # Memcached
```

## Performance Optimization

### 1. PHP Optimization

#### PHP-FPM Configuration
```ini
# /etc/php/8.4/fpm/pool.d/www.conf
[www]
user = www-data
group = www-data

listen = /run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.process_idle_timeout = 10s

; Increase memory limit for DNS operations
php_admin_value[memory_limit] = 512M

; Optimize for long-running processes
php_admin_value[max_execution_time] = 60
php_admin_value[max_input_time] = 30

; Enable OPcache
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 256
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 20000
php_admin_value[opcache.validate_timestamps] = 0
```

### 2. Database Optimization

#### MySQL Configuration
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# Buffer pool size (70-80% of available RAM for dedicated DB server)
innodb_buffer_pool_size = 4G
innodb_buffer_pool_instances = 4

# Log file settings
innodb_log_file_size = 512M
innodb_log_buffer_size = 64M

# Connection settings
max_connections = 200
max_connect_errors = 1000

# Query cache (if using MySQL 5.7)
query_cache_type = 1
query_cache_size = 256M

# Slow query log
slow_query_log = 1
long_query_time = 2
slow_query_log_file = /var/log/mysql/slow.log

# Index optimization
key_buffer_size = 256M
max_allowed_packet = 64M

# DNS-specific optimizations
# Optimize for DNS cache table queries
innodb_flush_log_at_trx_commit = 2
innodb_thread_concurrency = 8
```

### 3. Redis Optimization

#### Redis Configuration
```ini
# /etc/redis/redis.conf
# Memory usage
maxmemory 2gb
maxmemory-policy allkeys-lru

# Persistence (adjust based on needs)
save 900 1
save 300 10
save 60 10000

# Network
tcp-keepalive 300
timeout 0

# Performance
tcp-backlog 511
databases 16

# DNS-specific settings
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
```

## Docker Deployment

### 1. Docker Compose Configuration

#### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: project-namer-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./storage/logs:/var/www/html/storage/logs
    networks:
      - project-namer
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=database
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
      - CACHE_DRIVER=redis

  dns-worker:
    build:
      context: .
      dockerfile: Dockerfile.worker
    container_name: project-namer-dns-worker
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./storage/logs:/var/www/html/storage/logs
    networks:
      - project-namer
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
    command: php artisan queue:work --queue=dns --sleep=3 --tries=3 --timeout=30
    deploy:
      replicas: 4

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: project-namer-scheduler
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - project-namer
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis
    command: php artisan schedule:work

  nginx:
    image: nginx:1.20
    container_name: project-namer-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx:/etc/nginx/conf.d
      - ./docker/ssl:/etc/ssl/certs
    networks:
      - project-namer
    depends_on:
      - app

  database:
    image: mysql:8.0
    container_name: project-namer-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: project_namer
      MYSQL_USER: project_namer
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - database_data:/var/lib/mysql
      - ./docker/mysql:/etc/mysql/conf.d
    networks:
      - project-namer
    command: mysqld --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci

  redis:
    image: redis:6.2
    container_name: project-namer-redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
      - ./docker/redis/redis.conf:/usr/local/etc/redis/redis.conf
    networks:
      - project-namer
    command: redis-server /usr/local/etc/redis/redis.conf

networks:
  project-namer:
    driver: bridge

volumes:
  database_data:
  redis_data:
```

#### Dockerfile
```dockerfile
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

# Create system user
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Copy application files
COPY . /var/www/html
COPY --chown=www:www . /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www:www /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chmod -R 755 /var/www/html/bootstrap/cache

USER www

EXPOSE 9000

CMD ["php-fpm"]
```

#### Dockerfile.worker
```dockerfile
FROM php:8.4-cli

WORKDIR /var/www/html

# Install dependencies (same as main Dockerfile)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

COPY . /var/www/html
COPY --chown=www:www . /var/www/html

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www:www /var/www/html

USER www

CMD ["php", "artisan", "queue:work", "--queue=dns"]
```

### 2. Docker Deployment Commands

```bash
# Build and start containers
docker-compose up -d --build

# Scale DNS workers
docker-compose up -d --scale dns-worker=6

# Run migrations
docker-compose exec app php artisan migrate

# View logs
docker-compose logs -f dns-worker

# Restart services
docker-compose restart app dns-worker

# Stop all services
docker-compose down
```

## Cloud Deployment

### 1. AWS Deployment

#### ECS Service Definition
```json
{
  "family": "project-namer",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "1024",
  "memory": "2048",
  "executionRoleArn": "arn:aws:iam::ACCOUNT:role/ecsTaskExecutionRole",
  "taskRoleArn": "arn:aws:iam::ACCOUNT:role/ecsTaskRole",
  "containerDefinitions": [
    {
      "name": "app",
      "image": "your-registry/project-namer:latest",
      "portMappings": [
        {
          "containerPort": 9000,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {"name": "APP_ENV", "value": "production"},
        {"name": "DB_HOST", "value": "your-rds-endpoint"},
        {"name": "REDIS_HOST", "value": "your-elasticache-endpoint"}
      ],
      "secrets": [
        {
          "name": "DB_PASSWORD",
          "valueFrom": "arn:aws:secretsmanager:region:account:secret:db-password"
        }
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/project-namer",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      }
    }
  ]
}
```

#### Application Load Balancer
```yaml
# CloudFormation template snippet
Resources:
  LoadBalancer:
    Type: AWS::ElasticLoadBalancingV2::LoadBalancer
    Properties:
      Name: project-namer-alb
      Scheme: internet-facing
      Type: application
      Subnets:
        - !Ref PublicSubnet1
        - !Ref PublicSubnet2
      SecurityGroups:
        - !Ref ALBSecurityGroup

  TargetGroup:
    Type: AWS::ElasticLoadBalancingV2::TargetGroup
    Properties:
      Name: project-namer-tg
      Port: 80
      Protocol: HTTP
      VpcId: !Ref VPC
      TargetType: ip
      HealthCheckPath: /health
      HealthCheckIntervalSeconds: 30
      HealthyThresholdCount: 2
      UnhealthyThresholdCount: 3
```

### 2. Google Cloud Platform

#### Cloud Run Deployment
```yaml
# cloudbuild.yaml
steps:
  # Build the container image
  - name: 'gcr.io/cloud-builders/docker'
    args: ['build', '-t', 'gcr.io/$PROJECT_ID/project-namer:$COMMIT_SHA', '.']

  # Push the container image to Container Registry
  - name: 'gcr.io/cloud-builders/docker'
    args: ['push', 'gcr.io/$PROJECT_ID/project-namer:$COMMIT_SHA']

  # Deploy to Cloud Run
  - name: 'gcr.io/cloud-builders/gcloud'
    args:
    - 'run'
    - 'deploy'
    - 'project-namer'
    - '--image'
    - 'gcr.io/$PROJECT_ID/project-namer:$COMMIT_SHA'
    - '--region'
    - 'us-central1'
    - '--platform'
    - 'managed'
    - '--allow-unauthenticated'
    - '--max-instances'
    - '10'
    - '--memory'
    - '2Gi'
    - '--cpu'
    - '2'
    - '--set-env-vars'
    - 'APP_ENV=production'
```

## Rollback Procedures

### 1. Application Rollback

#### Git-based Rollback
```bash
# Identify current and previous versions
git log --oneline -10

# Rollback to previous commit
git checkout <previous-commit-hash>

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan cache:clear
php artisan config:cache

# Restart services
sudo systemctl restart php8.4-fpm
sudo supervisorctl restart project-namer-dns-worker:*
```

#### Database Rollback
```bash
# Rollback migrations (be very careful!)
php artisan migrate:rollback --step=1

# Or rollback to specific batch
php artisan migrate:rollback --batch=5

# Verify database state
php artisan migrate:status
```

### 2. Docker Rollback

```bash
# Rollback to previous image
docker-compose down
docker-compose pull previous-tag
docker-compose up -d

# Or specify specific image version
docker-compose -f docker-compose.yml -f docker-compose.rollback.yml up -d
```

### 3. Load Balancer Rollback

#### Nginx Rollback
```bash
# Keep backup of working configuration
sudo cp /etc/nginx/sites-available/project-namer /etc/nginx/sites-available/project-namer.backup

# Restore from backup
sudo cp /etc/nginx/sites-available/project-namer.backup /etc/nginx/sites-available/project-namer

# Test and reload
sudo nginx -t
sudo systemctl reload nginx
```

## Post-Deployment Verification

### 1. Application Health Checks

#### Automated Verification Script
```bash
#!/bin/bash
# save as scripts/deployment-verification.sh

BASE_URL="${1:-https://your-domain.com}"
FAILED_CHECKS=0

echo "Starting post-deployment verification for $BASE_URL"

# Check 1: Application responds
echo -n "Checking application health... "
if curl -sf "$BASE_URL/health" > /dev/null; then
    echo "✓ PASSED"
else
    echo "✗ FAILED"
    ((FAILED_CHECKS++))
fi

# Check 2: DNS health
echo -n "Checking DNS health... "
dns_response=$(curl -s "$BASE_URL/health/dns")
dns_status=$(echo "$dns_response" | jq -r '.overall_status' 2>/dev/null)
if [ "$dns_status" = "healthy" ] || [ "$dns_status" = "warning" ]; then
    echo "✓ PASSED ($dns_status)"
else
    echo "✗ FAILED ($dns_status)"
    ((FAILED_CHECKS++))
fi

# Check 3: Cache health
echo -n "Checking cache health... "
cache_response=$(curl -s "$BASE_URL/health/cache")
cache_status=$(echo "$cache_response" | jq -r '.status' 2>/dev/null)
if [ "$cache_status" = "healthy" ]; then
    echo "✓ PASSED"
else
    echo "✗ FAILED ($cache_status)"
    ((FAILED_CHECKS++))
fi

# Check 4: Queue health
echo -n "Checking queue health... "
queue_response=$(curl -s "$BASE_URL/health/queue")
queue_status=$(echo "$queue_response" | jq -r '.status' 2>/dev/null)
if [ "$queue_status" = "healthy" ] || [ "$queue_status" = "warning" ]; then
    echo "✓ PASSED ($queue_status)"
else
    echo "✗ FAILED ($queue_status)"
    ((FAILED_CHECKS++))
fi

# Check 5: DNS functionality
echo -n "Testing DNS functionality... "
if php artisan dns:test:basic > /dev/null 2>&1; then
    echo "✓ PASSED"
else
    echo "✗ FAILED"
    ((FAILED_CHECKS++))
fi

# Summary
echo ""
echo "Verification Summary:"
echo "===================="
if [ $FAILED_CHECKS -eq 0 ]; then
    echo "✓ All checks passed! Deployment successful."
    exit 0
else
    echo "✗ $FAILED_CHECKS check(s) failed! Review deployment."
    exit 1
fi
```

### 2. Performance Verification

```bash
# Test DNS performance
php artisan dns:test:performance --duration=60

# Test application performance
ab -n 1000 -c 10 https://your-domain.com/

# Monitor queue processing
watch -n 5 'php artisan queue:monitor dns'
```

### 3. Monitoring Verification

```bash
# Check if monitoring endpoints are responding
curl -s https://your-domain.com/health/dns | jq .
curl -s https://your-domain.com/health/cache | jq .
curl -s https://your-domain.com/health/queue | jq .

# Verify worker processes
sudo supervisorctl status
ps aux | grep "queue:work"

# Check logs
tail -100 /var/www/project-namer/storage/logs/laravel.log
tail -50 /var/www/project-namer/storage/logs/dns-worker.log
```

## Deployment Checklist

### Pre-Deployment
- [ ] Environment variables configured
- [ ] Database migration tested
- [ ] Dependencies installed
- [ ] Tests passing
- [ ] Performance benchmarks recorded
- [ ] Backup procedures tested
- [ ] Monitoring configured

### Deployment
- [ ] Application deployed
- [ ] Database migrated
- [ ] Queue workers started
- [ ] Caches cleared and warmed
- [ ] Load balancer updated
- [ ] SSL certificates valid
- [ ] DNS propagated

### Post-Deployment
- [ ] Health checks passing
- [ ] DNS functionality verified
- [ ] Queue processing active
- [ ] Monitoring alerts configured
- [ ] Performance within acceptable limits
- [ ] Logs being generated correctly
- [ ] Backup verification successful

This comprehensive deployment guide covers all aspects of deploying the DNS filtering system in various environments. Choose the appropriate sections based on your specific deployment needs and infrastructure requirements.