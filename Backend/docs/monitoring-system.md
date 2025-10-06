# Admin Backend Monitoring System

## Overview

The Laravel Admin Backend includes a comprehensive monitoring and alerting system that provides real-time insights into system health, performance metrics, and automated alerting capabilities.

## Components

### 1. Health Check System

#### Endpoints
- `GET /api/health/ping` - Simple ping endpoint
- `GET /api/health` - Comprehensive health check (requires secret in production)
- `GET /api/health/database` - Database-specific health check
- `GET /api/health/cache` - Cache-specific health check

#### Console Commands
```bash
# Run health checks
php artisan admin:health:check

# Run specific component check
php artisan admin:health:check --component=database

# Output as JSON
php artisan admin:health:check --format=json

# Fail on warnings
php artisan admin:health:check --fail-on-warning
```

### 2. Metrics Collection

#### System Metrics Tracked
- Memory usage (current, peak, limit)
- Database performance (response time, connections)
- Cache performance (response time, hit rates)
- Queue status (pending jobs, failed jobs)
- Disk usage (free space, usage percentage)

#### Console Commands
```bash
# Collect metrics
php artisan admin:metrics:collect

# Collect with threshold checking
php artisan admin:metrics:collect --check-thresholds

# Clean up old metrics
php artisan admin:metrics:collect --cleanup

# Output to console
php artisan admin:metrics:collect --output=console
```

### 3. Continuous System Monitoring

#### Console Commands
```bash
# Basic monitoring
php artisan admin:monitor:system

# With health checks
php artisan admin:monitor:system --health-checks

# With alerting enabled
php artisan admin:monitor:system --alert-on-issues

# Custom interval and duration
php artisan admin:monitor:system --interval=30 --duration=1800
```

### 4. Alerting System

#### Alert Types
- **Performance Alerts**: Memory usage, disk space, response times
- **Health Check Alerts**: Component failures
- **Error Rate Alerts**: High error rates detected
- **System Error Alerts**: Critical system component failures

#### Notification Channels
- **Email**: SMTP-based email notifications
- **Slack**: Webhook-based Slack notifications
- **Discord**: Webhook-based Discord notifications

#### Console Commands
```bash
# Test alert system
php artisan admin:alert:test warning --message="Test message"

# Test specific channel
php artisan admin:alert:test critical --channel=email
```

### 5. Monitoring Dashboard

#### API Endpoints
- `GET /api/admin/monitoring/dashboard` - Complete dashboard data
- `GET /api/admin/monitoring/metrics?hours=24` - System metrics
- `GET /api/admin/monitoring/alerts?days=7` - Alert history
- `GET /api/admin/monitoring/performance?hours=24` - Performance analytics
- `POST /api/admin/monitoring/test-alert` - Test alert system

#### Console Dashboard
```bash
# Display monitoring dashboard
php artisan admin:monitoring:dashboard

# Refresh cached data
php artisan admin:monitoring:dashboard --refresh

# Show recent alerts
php artisan admin:monitoring:dashboard --alerts

# Show detailed metrics
php artisan admin:monitoring:dashboard --detailed
```

## Configuration

### Environment Variables

```env
# Health Checks
HEALTH_CHECK_ENABLED=true
HEALTH_CHECK_SECRET=your-secret-key

# Metrics Collection
METRICS_ENABLED=true
METRICS_COLLECTION_INTERVAL=900
METRICS_RETENTION_HOURS=168

# Performance Monitoring
APM_ENABLED=true
SLOW_REQUEST_THRESHOLD=1000
HIGH_MEMORY_THRESHOLD=50

# Alert Thresholds
ALERT_MEMORY_THRESHOLD=500
ALERT_DISK_THRESHOLD=90
ALERT_DB_RESPONSE_THRESHOLD=100
ALERT_CACHE_RESPONSE_THRESHOLD=50
ALERT_ERROR_RATE_THRESHOLD=5

# Error Monitoring
ERROR_MONITORING_ENABLED=true
ERROR_MONITORING_WINDOW=15
ERROR_RATE_THRESHOLD=5

# Notifications
MONITORING_NOTIFICATIONS_ENABLED=true
MONITORING_EMAIL_NOTIFICATIONS=true
MONITORING_EMAIL_RECIPIENTS=admin@example.com
MONITORING_SLACK_WEBHOOK=https://hooks.slack.com/...
MONITORING_DISCORD_WEBHOOK=https://discord.com/api/webhooks/...

# External Services
NEW_RELIC_ENABLED=false
NEW_RELIC_LICENSE_KEY=your-license-key
SENTRY_ENABLED=false
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### Configuration Files

#### `config/monitoring.php`
Main monitoring configuration including thresholds, notification settings, and external service integrations.

#### `config/production.php`
Production-specific monitoring settings including APM and error tracking configurations.

## Scheduled Tasks

The monitoring system includes several scheduled tasks configured in `routes/console.php`:

```php
// Health checks every 5 minutes
Schedule::command('admin:health:check --format=json')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Metrics collection every 15 minutes with threshold checking
Schedule::command('admin:metrics:collect --check-thresholds')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// System monitoring every 6 hours
Schedule::command('admin:monitor:system --interval=300 --duration=3600 --health-checks --alert-on-issues')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();

// Daily cleanup at 2 AM
Schedule::command('admin:metrics:collect --cleanup')
    ->dailyAt('02:00')
    ->withoutOverlapping();
```

## Middleware

### Application Performance Monitoring (APM)
- Tracks request response times
- Monitors memory usage per request
- Records slow requests and high memory usage
- Integrates with New Relic if available

### Error Tracking
- Monitors error rates across time windows
- Tracks specific error types
- Sends alerts when thresholds are exceeded
- Integrates with Sentry if configured

## Services

### MonitoringService
Core service for collecting and recording system metrics.

### AlertingService
Handles alert generation, throttling, and notification delivery across multiple channels.

## Security

### Production Health Checks
Health check endpoints require a secret token in production environments to prevent unauthorized access to system information.

### IP Whitelisting
Monitoring endpoints are protected by IP whitelisting middleware in production.

### Rate Limiting
All monitoring endpoints include appropriate rate limiting to prevent abuse.

## Integration with External Services

### New Relic APM
- Automatic transaction naming
- Custom event recording
- Error tracking integration
- Performance metrics collection

### Sentry Error Tracking
- Exception capture and reporting
- Performance monitoring
- Release tracking
- Environment-specific error filtering

### Uptime Monitoring
- Uptime Robot integration
- Pingdom integration
- Custom webhook support

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check database configuration
   - Verify connection credentials
   - Ensure database server is running

2. **Cache System Failures**
   - Verify Redis/cache configuration
   - Check cache driver settings
   - Ensure cache storage is accessible

3. **Alert Delivery Issues**
   - Verify notification channel configurations
   - Check webhook URLs and credentials
   - Review alert throttling settings

4. **High Resource Usage**
   - Monitor system metrics regularly
   - Adjust alert thresholds as needed
   - Optimize database queries and caching

### Logs

Monitoring activities are logged to various channels:
- Health checks: `storage/logs/laravel.log`
- Metrics collection: `storage/logs/laravel.log`
- Alerts: `storage/logs/laravel.log`
- Performance monitoring: APM middleware logs

## Best Practices

1. **Regular Monitoring**
   - Review dashboard daily
   - Monitor alert trends
   - Adjust thresholds based on usage patterns

2. **Alert Management**
   - Configure appropriate notification channels
   - Set reasonable alert thresholds
   - Review and act on alerts promptly

3. **Performance Optimization**
   - Monitor slow queries and requests
   - Optimize based on metrics data
   - Scale resources as needed

4. **Security**
   - Keep health check secrets secure
   - Regularly review access logs
   - Monitor for unusual activity patterns

## API Documentation

For detailed API documentation of monitoring endpoints, refer to the generated OpenAPI/Swagger documentation available at `/api/documentation` when the application is running.
