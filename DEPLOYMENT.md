# Deployment Guide

Production deployment checklist and procedures for Laravel CRM.

## Pre-Deployment Checklist

### Code & Testing
- [ ] All tests passing: `php artisan test --min=80`
- [ ] Code formatted: `./vendor/bin/pint`
- [ ] Code analyzed: `./vendor/bin/phpstan analyse`
- [ ] Security audit: `composer audit`
- [ ] PR reviewed and approved

### Environment Preparation
- [ ] `.env.production` configured
- [ ] Secrets in secrets manager (AWS Secrets Manager, HashiCorp Vault)
- [ ] Database backups enabled
- [ ] SSL certificates valid

## Database Setup

### PostgreSQL 14+

```bash
# Production server setup
sudo apt-get update
sudo apt-get install postgresql-14 postgresql-contrib-14

# Create database and user
sudo -u postgres psql

CREATE DATABASE laravel_crm;
CREATE USER crm_user WITH PASSWORD 'strong_password_here';
ALTER ROLE crm_user SET client_encoding TO 'utf8mb4';
ALTER ROLE crm_user SET default_transaction_isolation TO 'read committed';
ALTER ROLE crm_user SET default_transaction_deferrable TO on;
ALTER ROLE crm_user CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE laravel_crm TO crm_user;

# Enable extensions
\c laravel_crm
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

### Migrations

```bash
# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --class=StatusSeeder

# Verify
php artisan tinker
>>> DB::table('leads')->count()
```

## Cache & Storage

### Redis 6+

```bash
# Install Redis
sudo apt-get install redis-server

# Production config
sudo nano /etc/redis/redis.conf
# - requirepass your_redis_password
# - maxmemory 2gb
# - maxmemory-policy allkeys-lru

# Start service
sudo systemctl restart redis-server
```

### File Storage

```bash
# Create storage directories with proper permissions
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/logs

chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Configure S3 if needed
# .env: FILESYSTEM_DISK=s3
# .env: AWS_ACCESS_KEY_ID=xxx
# .env: AWS_SECRET_ACCESS_KEY=xxx
```

## Application Setup

### Installation

```bash
# Pull code
git clone https://github.com/org/laravel_crm.git /var/www/laravel_crm
cd /var/www/laravel_crm

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chown -R www-data:www-data /var/www/laravel_crm
```

### Configuration

```bash
# Generate app key
php artisan key:generate

# Cache config (improves performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Horizon
php artisan horizon:install
```

## Queue & Background Jobs

### Horizon (Job Queue Monitor)

```bash
# Create systemd service
sudo nano /etc/systemd/system/laravel-horizon.service

[Unit]
Description=Laravel Horizon
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/laravel_crm
ExecStart=/usr/bin/php artisan horizon
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target

# Enable and start
sudo systemctl enable laravel-horizon
sudo systemctl start laravel-horizon

# Monitor
php artisan horizon
# Visit http://your-domain/horizon
```

### Configuration

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'integrations_amocrm', 'integrations_retail', 'email'],
            'balance' => 'auto',
            'processes' => 4,
            'timeout' => 120,
            'tries' => 5,
            'nice' => 0,
        ],
    ],
],
```

## Web Server (Nginx)

```nginx
# /etc/nginx/sites-available/laravel_crm

server {
    listen 80;
    listen [::]:80;
    server_name laravel-crm.example.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name laravel-crm.example.com;

    # SSL Certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/laravel-crm.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/laravel-crm.example.com/privkey.pem;
    
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/laravel_crm/public;
    index index.php;

    # Client max body size
    client_max_body_size 10M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /vendor {
        deny all;
    }
}
```

## Monitoring & Logging

### Sentry Error Tracking

```php
// .env.production
SENTRY_LARAVEL_DSN=https://your-key@sentry.io/project-id

// config/sentry.php (optional custom config)
'traces_sample_rate' => 0.1,  // 10% of transactions
'environment' => 'production',
```

### Log Aggregation (ELK Stack or Datadog)

```bash
# Structured logging
tail -f storage/logs/laravel.log | grep -i error

# Check application health
curl https://laravel-crm.example.com/health

# Monitor with Datadog
# Update config/logging.php for Datadog driver
```

### Metrics & Alerts

```bash
# View Horizon metrics
php artisan horizon

# API response time monitoring
# Setup in datadog or prometheus
# Alert thresholds:
# - API latency > 2s
# - Queue depth > 5000
# - Error rate > 5%
# - Database connection errors
```

## Backup Strategy

### Database Backups

```bash
# Daily automated backup
0 2 * * * /usr/local/bin/backup-laravel-db.sh

# Script: /usr/local/bin/backup-laravel-db.sh
#!/bin/bash
BACKUP_DIR="/var/backups/laravel_crm"
DATE=$(date +%Y%m%d_%H%M%S)
pg_dump -h localhost -U crm_user laravel_crm | \
  gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"
# Keep 7 days of backups
find "$BACKUP_DIR" -mtime +7 -delete
```

### File Storage Backups

```bash
# S3 sync (if using S3)
0 3 * * * aws s3 sync /var/www/laravel_crm/storage/app s3://laravel-crm-backup/
```

## SSL/TLS Certificates

### Let's Encrypt with Certbot

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d laravel-crm.example.com

# Auto-renewal (runs daily)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Verify
sudo systemctl status certbot.timer
```

## Deployment Steps

```bash
# 1. Pull latest code
cd /var/www/laravel_crm
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Restart services
sudo systemctl restart laravel-horizon
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# 6. Health check
curl https://laravel-crm.example.com/health
```

## Rollback Procedure

```bash
# If deployment fails
cd /var/www/laravel_crm

# 1. Checkout previous version
git checkout previous-tag

# 2. Run migrations in reverse
php artisan migrate:rollback

# 3. Clear cache
php artisan cache:clear

# 4. Restart services
sudo systemctl restart laravel-horizon
sudo systemctl restart php8.2-fpm
```

## Performance Tuning

### PHP Configuration

```ini
# /etc/php/8.2/fpm/php.ini
memory_limit = 512M
max_execution_time = 60
max_input_vars = 5000
upload_max_filesize = 10M

# OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
```

### Database Optimization

```sql
-- Add missing indexes if needed
CREATE INDEX idx_leads_user_created ON leads(user_id, created_at DESC);
CREATE INDEX idx_leads_status ON leads(status, created_at DESC);
CREATE INDEX idx_leads_fingerprint ON leads(fingerprint);

-- Vacuum and analyze
VACUUM ANALYZE leads;
ANALYZE leads;
```

## Monitoring Checklist

- [ ] Sentry error tracking functional
- [ ] Horizon job queue stable
- [ ] Database backups running daily
- [ ] SSL certificates valid
- [ ] Redis cache healthy
- [ ] API response times < 2s
- [ ] Queue depth monitored
- [ ] Log files rotating properly

---

**Last Updated:** February 2026

**Support:** Contact ops-team@example.com for deployment assistance
