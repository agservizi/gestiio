# Observability Guide — Gestiio

Guida completa per tracciare errori, performance, e business events in Gestiio.

## 1. Error Tracking with Sentry

### Setup

```bash
# Install Sentry
composer require sentry/sentry-laravel

# Set DSN in .env
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx
SENTRY_ENVIRONMENT=production  # or staging, development
SENTRY_TRACES_SAMPLE_RATE=0.1  # Capture 10% of transactions
SENTRY_PROFILES_SAMPLE_RATE=0.1
```

### Configuration

**File**: `config/sentry.php`

```php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'environment' => env('SENTRY_ENVIRONMENT'),
    'traces_sample_rate' => 0.1,  // Performance monitoring
    'breadcrumbs' => [
        'logs' => true,           // Log messages → breadcrumbs
        'sql_queries' => true,    // DB queries → breadcrumbs
        'queue' => true,          // Queue jobs → breadcrumbs
        'cache' => true,          // Cache operations
        'http_client_requests' => true,
    ],
];
```

### Usage

Sentry automatically captures:
- ✅ Uncaught exceptions
- ✅ Log messages (level >= ERROR)
- ✅ Database queries
- ✅ Queue jobs
- ✅ Cache operations
- ✅ HTTP requests (breadcrumbs)

Manual capture:

```php
// Capture an exception manually
try {
    // ... code ...
} catch (\Exception $e) {
    \Sentry\captureException($e);
}

// Capture a message
\Sentry\captureMessage('Custom event', \Sentry\State\Scope::LEVEL_INFO);

// Add context
\Sentry\withScope(function (\Sentry\State\Scope $scope) {
    $scope->setContext('character', [
        'name' => 'Mighty Fighter',
        'level' => 19,
    ]);
    \Sentry\captureMessage('User logged in');
});
```

## 2. Structured Logging

### Log Channels

**File**: `config/logging.php`

| Channel | Purpose | TTL | Format |
|---------|---------|-----|--------|
| `structured` | General structured logs | N/A | JSON |
| `business_events` | Business logic events | N/A | JSON |
| `performance` | Performance metrics | N/A | JSON |
| `single` | Standard application logs | N/A | Plain text |
| `daily` | Daily rotated logs | 14 days | Plain text |

### Usage with StructuredLogger

**File**: `app/Services/StructuredLogger.php`

```php
use App\Services\StructuredLogger;

// Log business event
StructuredLogger::logBusinessEvent('order_created', [
    'order_id' => 123,
    'total' => 99.99,
    'items_count' => 5,
]);

// Log auth event
StructuredLogger::logAuthEvent('login', [
    'method' => 'password',
    'device' => 'mobile',
]);

// Log ticket event
StructuredLogger::logTicketEvent('created', $ticketId, [
    'priority' => 'high',
    'category' => 'bug',
]);

// Log contract event
StructuredLogger::logContractEvent('signed', $contractId, [
    'value' => 5000,
    'duration_months' => 12,
]);

// Log performance
StructuredLogger::logPerformance('report_generation', 2500, [
    'report_type' => 'monthly',
    'rows' => 50000,
]);

// Log API call
StructuredLogger::logApiCall('/api/contracts', 'GET', 200, 150);
```

### Log Output

All structured logs are JSON:

```json
{
    "event": "ticket.created",
    "timestamp": "2024-06-23T12:45:30Z",
    "user_id": 42,
    "data": {
        "ticket_id": 789,
        "priority": "high"
    }
}
```

### Reading Logs

```bash
# View structured business events
tail -f storage/logs/business-events.log | jq '.'

# View performance logs
tail -f storage/logs/performance.log | jq '.duration_ms'

# Filter by event
grep "auth.login" storage/logs/business-events.log | jq '.user_id'
```

## 3. Cache Strategy

### Overview

**File**: `app/Services/CacheStrategy.php`

Cache is critical for performance. Strategy:
- **User permissions**: 1 hour (rarely change)
- **User profile**: 30 minutes
- **User contracts**: 15 minutes (changes frequently)
- **User tickets**: 5 minutes (very dynamic)
- **Static data**: 1 week (never changes)
- **Search results**: 1 hour

### Usage

```php
use App\Services\CacheStrategy;

// Cache user permissions
$permissions = CacheStrategy::cacheUserPermissions($userId, function () {
    return auth()->user()->getAllPermissions();
});

// Cache contract list
$contracts = CacheStrategy::cacheUserContracts($userId, function () {
    return User::find($userId)->contratti()->get();
});

// Cache ticket list
$tickets = CacheStrategy::cacheUserTickets($userId, function () {
    return User::find($userId)->ticket()->get();
});

// Cache static data
$categories = CacheStrategy::cacheStaticData('ticket_categories', function () {
    return CausaleTicket::all();
});

// Cache search results
$results = CacheStrategy::cacheSearchResults($query, function () {
    return Azienda::search($query)->get();
});

// Invalidate cache when user updates
CacheStrategy::invalidateUserCache($userId);

// Invalidate all when user logs out
CacheStrategy::invalidateAllUserCache($userId);
```

## 4. Performance Monitoring

### Middleware

**File**: `app/Http/Middleware/LogPerformance.php`

Automatically logs:
- ✅ Slow requests (> 500ms)
- ✅ All API calls
- ✅ Response time in header: `X-Response-Time-Ms`

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\LogPerformance::class,
];
```

### Manual Performance Tracking

```php
$start = microtime(true);

// ... operation ...

$duration = (int) ((microtime(true) - $start) * 1000);
StructuredLogger::logPerformance('custom_operation', $duration);
```

### Performance Dashboard (with logs)

```bash
# Find slowest requests
grep "slow_request" storage/logs/performance.log | \
  jq '.duration_ms' | \
  sort -rn | head -10

# Find slow API endpoints
grep "api.call" storage/logs/business-events.log | \
  jq 'select(.duration_ms > 1000) | .endpoint'

# Average response time by endpoint
grep "api.call" storage/logs/business-events.log | \
  jq -s 'group_by(.endpoint) | map({endpoint: .[0].endpoint, avg_ms: (map(.duration_ms) | add / length)}) | sort_by(.avg_ms) | reverse'
```

## 5. Integration with GitHub Actions

CI pipeline (`.github/workflows/ci.yml`) runs:
- ✅ Linting (Pint)
- ✅ Static analysis (PHPStan)
- ✅ Tests (PHPUnit)
- ✅ Security audit (Composer)

Sentry & logging work in production/staging only.

## 6. Best Practices

### DO
- ✅ Log all business-critical events (auth, payments, contracts)
- ✅ Use structured logging for business events
- ✅ Cache frequently accessed data
- ✅ Monitor slow queries (500ms+ in logs)
- ✅ Use Sentry for production errors
- ✅ Set appropriate TTLs based on data volatility

### DON'T
- ❌ Cache sensitive data (passwords, tokens)
- ❌ Log PII (emails, phone numbers) unless necessary
- ❌ Set cache TTL too high (staleness issues)
- ❌ Leave Sentry sampling at 100% (costs $$$)

## 7. Troubleshooting

### Sentry DSN not working?
```bash
php artisan tinker
>>> \Sentry\captureMessage('Test');
>>> # Check Sentry dashboard
```

### Logs not appearing?
```bash
# Check logging is enabled for the channel
tail -f storage/logs/business-events.log

# Check permissions
ls -la storage/logs/
```

### Cache not invalidating?
```bash
php artisan cache:clear
# Or specific cache key
\Illuminate\Support\Facades\Cache::forget("user.profile.{$userId}");
```

## Resources

- [Sentry Laravel Docs](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Laravel Logging](https://laravel.com/docs/9.x/logging)
- [Laravel Caching](https://laravel.com/docs/9.x/cache)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
