# Production Deployment Checklist

Verifica completa prima di mettere in produzione.

## Pre-Deployment (1 week before)

### Environment & Configuration
- [ ] `.env` configured with production values
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `CACHE_DRIVER=redis` (not file)
- [ ] `QUEUE_CONNECTION=redis` (not sync)
- [ ] `SESSION_DRIVER=redis`
- [ ] `LOG_CHANNEL=stack` (logs to multiple channels)

### Security
- [ ] `APP_KEY` set and different from staging
- [ ] HTTPS enabled and certificate valid
- [ ] `SENTRY_LARAVEL_DSN` configured
- [ ] All API keys in `.env` (not hardcoded)
- [ ] Database credentials rotated
- [ ] Redis password set (if using Redis)

### Database
- [ ] All migrations ran: `php artisan migrate --force`
- [ ] Database backup created: `php artisan backup:run`
- [ ] Backup verified (test restore)
- [ ] Indexes created on all foreign keys
- [ ] Database user has minimal permissions

### Code Quality
- [ ] All tests pass: `php artisan test`
- [ ] Lint check passes: `./vendor/bin/pint --test`
- [ ] Static analysis passes: `./vendor/bin/phpstan analyse`
- [ ] No `dd()`, `dump()`, or `var_dump()` in code
- [ ] All deprecated functions removed

### Performance
- [ ] Cache warming enabled (if needed)
- [ ] Database query optimization verified
- [ ] No N+1 queries in critical paths
- [ ] Static assets minified and compressed
- [ ] CDN configured for static files (if applicable)

## Deployment Day

### Pre-Deployment
- [ ] Notify users of maintenance window
- [ ] Create database backup immediately before
- [ ] Create Git tag: `git tag v1.0.0-prod`

### Deploy Steps
1. Pull latest code: `git fetch && git checkout v1.0.0-prod`
2. Install dependencies: `composer install --no-dev`
3. Build assets: `npm run prod`
4. Run migrations: `php artisan migrate --force`
5. Cache config: `php artisan config:cache`
6. Cache routes: `php artisan route:cache`
7. Clear all caches: `php artisan cache:clear`
8. Reset permissions: `php artisan permission:cache-reset`
9. Restart queue: `supervisorctl restart laravel-worker`
10. Restart PHP-FPM: `systemctl restart php8.2-fpm`

### Post-Deployment (Smoke Tests)
- [ ] Website loads (check 200 status)
- [ ] Login works (email/password)
- [ ] 2FA works (OTP sent)
- [ ] Tickets can be created
- [ ] Contracts can be viewed
- [ ] Dashboard loads
- [ ] Chat messages send (push notifications work)
- [ ] Files can be uploaded
- [ ] Export functionality works

## Monitoring (First 24 hours)

### Sentry
- [ ] Check Sentry dashboard for errors
- [ ] Set up alerts for new errors
- [ ] Monitor error rate (should be < 0.1%)

### Logs
- [ ] Monitor `storage/logs/laravel.log`
- [ ] Monitor `storage/logs/business-events.log`
- [ ] Monitor `storage/logs/performance.log`
- [ ] Look for: errors, slow requests (> 500ms), failed jobs

### Performance
- [ ] Monitor response times (should be < 200ms)
- [ ] Monitor database connection pool
- [ ] Monitor Redis memory usage
- [ ] Monitor queue depth (should stay low)

### User Feedback
- [ ] Check support tickets for issues
- [ ] Monitor user complaints in chat
- [ ] Be ready to rollback if critical issues

## Rollback Plan

If critical issues occur:

1. Notify users immediately
2. Rollback code: `git checkout {previous-tag}`
3. Rebuild assets: `npm run prod`
4. Rollback database (if needed): `php artisan db-snapshots:load {backup-name}`
5. Restart services: `supervisorctl restart laravel-worker`
6. Verify smoke tests again

## Post-Deployment (First Week)

- [ ] Monitor error rates (target: < 0.1%)
- [ ] Monitor performance metrics
- [ ] Monitor queue job failures
- [ ] Monitor slow query logs
- [ ] Review user feedback
- [ ] Review Sentry issues
- [ ] Verify backups are working
- [ ] Test disaster recovery procedure

## Ongoing Production Monitoring

### Daily
- [ ] Check Sentry for new errors
- [ ] Review application logs
- [ ] Check queue status
- [ ] Monitor disk space

### Weekly
- [ ] Review performance metrics
- [ ] Review user feedback
- [ ] Run database integrity check: `php artisan tinker`
- [ ] Verify backups completed

### Monthly
- [ ] Review and clean up old logs
- [ ] Test database restore procedure
- [ ] Review and update dependencies
- [ ] Performance tuning (if needed)

## Runbook: Common Issues

### Queue Not Processing
```bash
supervisorctl status laravel-worker
supervisorctl restart laravel-worker
php artisan queue:work --verbose
```

### Push Notifications Not Working
```bash
# Check VAPID keys in .env
php artisan tinker
>>> config('sentry.dsn')
# Restart queue worker
supervisorctl restart laravel-worker
```

### Slow Queries
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog(); DB::table('tickets')->get(); dd(DB::getQueryLog());
# Check for N+1 queries
# Add with() for relationships
```

### High Memory Usage
```bash
# Check queue job size
php artisan queue:work --max-jobs=100

# Restart PHP-FPM
systemctl restart php8.2-fpm

# Check cache size
redis-cli INFO memory
```

