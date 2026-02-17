# Troubleshooting Guide

Common issues and solutions for Laravel CRM.

## Integration Issues

### "Integration failed to send"

**Symptoms:**
- Leads not appearing in external systems
- Horizon shows failed jobs
- Sentry reports integration errors

**Diagnosis:**

```bash
# 1. Check error logs
tail -f storage/logs/laravel.log | grep -i integration

# 2. Check Sentry dashboard
# Visit https://sentry.io and filter by integration errors

# 3. Review failed job details
php artisan tinker
>>> DB::table('failed_jobs')->latest()->first();

# 4. Check integration credentials
>>> DB::table('integration_credentials')->where('type', 'amocrm')->first();
```

**Solutions:**

```bash
# Verify API credentials
# 1. Login to the external platform (AmoCRM, RetailCRM, etc.)
# 2. Regenerate API key if needed
# 3. Update credentials in database or .env

# Retry failed jobs
php artisan queue:retry failed

# Check integration config
cat config/integrations.php | grep -A 20 amocrm

# Test connectivity
php artisan tinker
>>> $integration = new App\Services\Integration\AmoCrmIntegration(['access_token' => '...']);
>>> $integration->validate();

# Check rate limits not exceeded
>>> DB::table('integration_logs')->where('type', 'amocrm')->latest()->first();
```

---

### "Queue not processing"

**Symptoms:**
- Jobs stuck in queue
- Horizon showing paused
- New leads not being sent to integrations

**Diagnosis:**

```bash
# 1. Check Redis connection
redis-cli ping
# Should return: PONG

# 2. Check Horizon status
ps aux | grep horizon

# 3. Check failed jobs
php artisan queue:failed

# 4. Monitor queue depth
php artisan tinker
>>> DB::table('jobs')->count();
>>> DB::connection('redis')->get('queues');
```

**Solutions:**

```bash
# Restart Horizon
sudo systemctl restart laravel-horizon

# Or manually:
php artisan horizon:terminate
php artisan horizon

# Clear stuck jobs (careful!)
php artisan queue:flush

# Retry specific failed job
php artisan queue:retry 1

# Retry all failed jobs
php artisan queue:retry all

# Check Redis memory
redis-cli INFO memory
# If memory is full:
redis-cli FLUSHDB  # ⚠️ Warning: deletes all queue data!
```

---

## Database Issues

### "Connection refused" to PostgreSQL

**Diagnosis:**

```bash
# Test connection
psql -h localhost -U crm_user -d laravel_crm

# Check if service is running
sudo systemctl status postgresql

# Check logs
sudo tail -f /var/log/postgresql/postgresql.log
```

**Solutions:**

```bash
# Restart PostgreSQL
sudo systemctl restart postgresql

# Check user permissions
sudo -u postgres psql -c "\du"

# Verify connection string
# In .env:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_crm
DB_USERNAME=crm_user
DB_PASSWORD=xxxxxx

# Test from application
php artisan tinker
>>> DB::connection()->getPdo();
```

---

### "Migrations failed"

**Diagnosis:**

```bash
# Check migration status
php artisan migrate:status

# See recent migrations
git log --oneline database/migrations/ | head -5
```

**Solutions:**

```bash
# Rollback last batch
php artisan migrate:rollback

# Check for syntax errors
php artisan migrate --step=1 --verbose

# Manual fix (if database corrupted)
php artisan migrate:refresh --seed  # ⚠️ Deletes all data!
```

---

## API Issues

### "401 Unauthorized" errors

**Diagnosis:**

```bash
# 1. Check if token is present
# In request headers:
Authorization: Bearer <token>

# 2. Verify token is valid
php artisan tinker
>>> app('auth.jwt.provider')->decode($token);

# 3. Check .env
JWT_SECRET=xxxxx
JWT_ALGORITHM=HS256
```

**Solutions:**

```bash
# Regenerate JWT secret
php artisan jwt:secret

# Test token generation
php artisan tinker
>>> auth('api')->attempt(['email' => 'user@example.com', 'password' => 'password']);
```

---

### "429 Too Many Requests"

**Diagnosis:**

```bash
# Check rate limit config
cat config/rate_limits.php

# Check your IP in rate limiter
php artisan tinker
>>> DB::table('rate_limit_logs')->where('ip', '123.45.67.89')->latest()->first();
```

**Solutions:**

```php
// Whitelist your IP temporarily
// .env:
RATE_LIMIT_WHITELIST_IPS=123.45.67.89,198.51.100.1

// Or increase limits
RATE_LIMIT_CLIENT_LIMIT=500
RATE_LIMIT_CLIENT_WINDOW=60
```

---

### "Invalid input" validation errors

**Diagnosis:**

```bash
# Check validation rules
cat app/Http/Requests/CreateLeadRequest.php

# Test with curl
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Content-Type: application/json" \
  -d '{"name": "John", "email": "invalid"}'
```

**Solutions:**

```bash
# Review error response
{
  "errors": {
    "email": ["The email field must be a valid email address."],
    "phone": ["The phone field must match E.164 format."]
  }
}

# Fix your request data:
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+12345678901"
  }'
```

---

## Performance Issues

### "API response time slow" (> 2 seconds)

**Diagnosis:**

```bash
# Check query execution time
# Enable query logging in .env:
DB_LOG_QUERIES=true

# Review slow queries
tail -f storage/logs/query.log

# Check database indexes
\d leads  # In psql

# Identify N+1 queries
php artisan tinker
>>> DB::enableQueryLog();
>>> $leads = App\Models\Lead::all();
>>> foreach ($leads as $lead) { echo $lead->user->name; }  // N+1!
>>> count(DB::getQueryLog());
```

**Solutions:**

```php
// Use eager loading (avoid N+1)
$leads = Lead::with('user')->get();  // Good ✓
// Instead of:
$leads = Lead::all();
foreach ($leads as $lead) {
    echo $lead->user->name;  // N queries! ✗
}

// Add database indexes
// In migration:
Schema::table('leads', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']);
});

// Cache frequently accessed data
$statuses = Cache::remember('lead_statuses', 3600, function () {
    return Status::all();
});
```

---

### "High memory usage"

**Diagnosis:**

```bash
# Check process memory
ps aux | grep "php\|horizon" | grep -v grep

# Check Redis memory
redis-cli INFO memory

# Check database connections
\conninfo  # In psql
```

**Solutions:**

```php
// Use chunking for large datasets
Lead::chunk(1000, function ($leads) {
    foreach ($leads as $lead) {
        // Process lead
    }
});

// Instead of:
$leads = Lead::all();  // All in memory!

// Cache to reduce queries
$leads = Cache::remember('leads_count', 3600, fn() => Lead::count());

// Limit results
$leads = Lead::paginate(50);
```

---

## Security Issues

### "Suspicious activity detected"

**Diagnosis:**

```bash
# Check firewall/IP filter logs
tail -f storage/logs/laravel.log | grep "ip_filter\|blocked"

# Review request patterns
php artisan tinker
>>> DB::table('request_logs')
  ->where('ip_address', '203.0.113.45')
  ->latest()
  ->paginate();
```

**Solutions:**

```php
// Whitelist legitimate IPs
// In .env:
IP_WHITELIST=203.0.113.45,198.51.100.1

// Review integration credentials for leaks
>>> DB::table('integration_credentials')->pluck('credentials');

// Rotate API keys immediately if leaked
// Update config/integrations.php with new credentials
```

---

### "Data validation bypass"

**Diagnosis:**

```bash
# Test input validation
curl -X POST http://localhost:8000/api/v1/leads \
  -d 'name=<script>alert("xss")</script>'

# Check sanitization in CreateLeadRequest
cat app/Http/Requests/CreateLeadRequest.php | grep sanitize
```

**Solutions:**

```php
// Ensure input validation is active
// Verify rules in CreateLeadRequest:
'name' => 'required|string|max:255|regex:/^[a-zA-Z\s\-\'\.]+$/',

// Test with valid data only:
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe"}'
```

---

## Deployment Issues

### "Composer install fails"

**Diagnosis:**

```bash
composer install -v  # Verbose output
```

**Solutions:**

```bash
# Clear cache
composer clear-cache

# Update composer
composer self-update

# Check PHP version
php -v  # Should be 8.2+

# Install missing extensions
sudo apt-get install php8.2-dom php8.2-mbstring php8.2-mysql
```

---

### "Cache issues after deploy"

**Diagnosis:**

```bash
# Check cached files
ls -la bootstrap/cache/

# Verify cache is cleared
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Solutions:**

```bash
# Full cache clear
php artisan cache:clear && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear

# Rebuild caches
php artisan config:cache && \
php artisan route:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart laravel-horizon
```

---

## Health Check

```bash
# Comprehensive health check script
#!/bin/bash

echo "=== Laravel CRM Health Check ==="

# 1. Database
echo -n "✓ Database: "
php artisan tinker --execute "echo DB::connection()->getDatabaseName() . ' OK'" 2>/dev/null || echo "FAILED"

# 2. Cache
echo -n "✓ Cache: "
php artisan tinker --execute "Cache::put('test', 'ok', 60); echo (Cache::get('test') === 'ok' ? 'OK' : 'FAILED')" 2>/dev/null

# 3. Queue
echo -n "✓ Queue: "
ps aux | grep horizon | grep -v grep > /dev/null && echo "Running" || echo "Stopped"

# 4. Redis
echo -n "✓ Redis: "
redis-cli ping 2>/dev/null || echo "Not responding"

# 5. API
echo -n "✓ API: "
curl -s http://localhost/api/v1/leads -H "Authorization: Bearer test" | grep -q "error" && echo "Responding" || echo "Check token"

echo "=== End Health Check ==="
```

---

**Last Updated:** February 2026

**Emergency Contact:** ops-team@example.com | Slack: #ops-emergency
