<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the admin backend
    | monitoring system including health checks, metrics collection,
    | and alerting thresholds.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    'health_checks' => [
        'enabled' => env('HEALTH_CHECK_ENABLED', true),
        'secret' => env('HEALTH_CHECK_SECRET'),
        'endpoints' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
        ],
        'timeout' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection Configuration
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'collection_interval' => env('METRICS_COLLECTION_INTERVAL', 900), // 15 minutes
        'retention_hours' => env('METRICS_RETENTION_HOURS', 168), // 7 days
        'storage' => [
            'driver' => 'cache', // cache, database, file
            'cache_prefix' => 'admin_metrics_',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enabled' => env('APM_ENABLED', true),
        'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 1000), // milliseconds
        'high_memory_threshold' => env('HIGH_MEMORY_THRESHOLD', 50), // MB
        'track_queries' => env('TRACK_QUERIES', true),
        'track_cache_hits' => env('TRACK_CACHE_HITS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'memory_usage_threshold' => env('ALERT_MEMORY_THRESHOLD', 500), // MB
        'disk_usage_threshold' => env('ALERT_DISK_THRESHOLD', 90), // percentage
        'database_response_threshold' => env('ALERT_DB_RESPONSE_THRESHOLD', 100), // milliseconds
        'cache_response_threshold' => env('ALERT_CACHE_RESPONSE_THRESHOLD', 50), // milliseconds
        'error_rate_threshold' => env('ALERT_ERROR_RATE_THRESHOLD', 5), // errors per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | New Relic Configuration
    |--------------------------------------------------------------------------
    */
    'new_relic' => [
        'enabled' => env('NEW_RELIC_ENABLED', false),
        'license_key' => env('NEW_RELIC_LICENSE_KEY'),
        'app_name' => env('NEW_RELIC_APP_NAME', 'Nhật Anh Admin Backend'),
        'record_sql' => env('NEW_RELIC_RECORD_SQL', 'obfuscated'),
        'capture_params' => env('NEW_RELIC_CAPTURE_PARAMS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentry Configuration
    |--------------------------------------------------------------------------
    */
    'sentry' => [
        'enabled' => env('SENTRY_ENABLED', false),
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'release' => env('SENTRY_RELEASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channels' => [
            'health_checks' => 'daily',
            'metrics' => 'daily',
            'performance' => 'daily',
            'alerts' => 'daily',
        ],
        'retention_days' => env('LOG_RETENTION_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Monitoring Services
    |--------------------------------------------------------------------------
    */
    'external_services' => [
        'uptime_robot' => [
            'enabled' => env('UPTIME_ROBOT_ENABLED', false),
            'api_key' => env('UPTIME_ROBOT_API_KEY'),
        ],
        'pingdom' => [
            'enabled' => env('PINGDOM_ENABLED', false),
            'api_key' => env('PINGDOM_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Rate Monitoring
    |--------------------------------------------------------------------------
    */
    'error_monitoring' => [
        'enabled' => env('ERROR_MONITORING_ENABLED', true),
        'time_window_minutes' => env('ERROR_MONITORING_WINDOW', 15),
        'error_rate_threshold' => env('ERROR_RATE_THRESHOLD', 5),
        'track_error_types' => [
            'ValidationException',
            'ModelNotFoundException',
            'AuthenticationException',
            'AuthorizationException',
            'QueryException',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => env('MONITORING_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'email' => env('MONITORING_EMAIL_NOTIFICATIONS', true),
            'slack' => env('MONITORING_SLACK_NOTIFICATIONS', false),
            'discord' => env('MONITORING_DISCORD_NOTIFICATIONS', false),
        ],
        'recipients' => [
            'email' => env('MONITORING_EMAIL_RECIPIENTS', 'admin@nhatanh.dev'),
            'slack_webhook' => env('MONITORING_SLACK_WEBHOOK'),
            'discord_webhook' => env('MONITORING_DISCORD_WEBHOOK'),
        ],
        'throttle' => [
            'same_alert_minutes' => 30, // Don't send same alert within 30 minutes
            'max_alerts_per_hour' => 10, // Maximum alerts per hour
        ],
    ],

];
