<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Production Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains production-specific settings for the
    | Nhật Anh Dev Admin Backend API. These settings are optimized for
    | production deployment with security, performance, and monitoring.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SSL/HTTPS Configuration
    |--------------------------------------------------------------------------
    */
    'ssl' => [
        'force_https' => env('FORCE_HTTPS', true),
        'ssl_redirect' => env('SSL_REDIRECT', true),
        'secure_proxy_ssl_header' => env('SECURE_PROXY_SSL_HEADER', 'HTTP_X_FORWARDED_PROTO'),
        'trusted_proxies' => env('TRUSTED_PROXIES', '*'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    */
    'security_headers' => [
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],
        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; media-src 'self'; object-src 'none'; child-src 'none'; worker-src 'none'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';",
        ],
        'permissions_policy' => [
            'enabled' => env('SECURITY_PERMISSIONS_POLICY_ENABLED', true),
            'policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Pool Configuration
    |--------------------------------------------------------------------------
    */
    'database_pool' => [
        'enabled' => true,
        'min_connections' => env('DB_POOL_MIN_CONNECTIONS', 5),
        'max_connections' => env('DB_POOL_MAX_CONNECTIONS', 20),
        'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60000),
        'timeout' => env('DB_POOL_TIMEOUT', 30000),
        'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 300000),
        'max_lifetime' => env('DB_POOL_MAX_LIFETIME', 1800000),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    */
    'cdn' => [
        'enabled' => env('CDN_ENABLED', true),
        'url' => env('CDN_URL'),
        'assets_path' => 'assets',
        'images_path' => 'images',
        'cache_control' => 'max-age=31536000, public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'health_checks' => [
            'enabled' => env('HEALTH_CHECK_ENABLED', true),
            'secret' => env('HEALTH_CHECK_SECRET'),
            'endpoints' => [
                'database' => true,
                'cache' => true,
                'storage' => true,
                'queue' => true,
            ],
        ],
        'apm' => [
            'enabled' => env('APM_ENABLED', true),
            'new_relic' => [
                'license_key' => env('NEW_RELIC_LICENSE_KEY'),
                'app_name' => env('NEW_RELIC_APP_NAME'),
            ],
        ],
        'error_tracking' => [
            'enabled' => env('ERROR_TRACKING_ENABLED', true),
            'sentry' => [
                'dsn' => env('SENTRY_LARAVEL_DSN'),
                'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        's3_bucket' => env('BACKUP_S3_BUCKET'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'schedule' => [
            'database' => '0 2 * * *', // Daily at 2 AM
            'files' => '0 3 * * 0',   // Weekly on Sunday at 3 AM
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'opcache' => [
            'enabled' => true,
            'validate_timestamps' => false,
            'max_accelerated_files' => 20000,
            'memory_consumption' => 256,
        ],
        'query_cache' => [
            'enabled' => true,
            'ttl' => 3600,
        ],
        'response_cache' => [
            'enabled' => true,
            'ttl' => 300,
        ],
    ],

];
