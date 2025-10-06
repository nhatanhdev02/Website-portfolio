<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | Laravel Admin Backend API. These settings control various security
    | measures including IP whitelisting, security headers, and audit logging.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | IP Whitelisting
    |--------------------------------------------------------------------------
    |
    | Configure IP addresses that are allowed to access admin endpoints.
    | Set 'enabled' to false to disable IP whitelisting entirely.
    | Use '*' in allowed_ips to allow all IPs (not recommended for production).
    |
    */

    'ip_whitelist' => [
        'enabled' => env('IP_WHITELIST_ENABLED', false),
        'allowed_ips' => array_filter(explode(',', env('ALLOWED_IPS', ''))),
        'bypass_in_local' => env('IP_WHITELIST_BYPASS_LOCAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers that will be added to all API responses.
    | These headers help protect against various security vulnerabilities.
    |
    */

    'headers' => [
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],

        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'policy' => env('SECURITY_CSP_POLICY', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none';"),
        ],

        'permissions_policy' => [
            'enabled' => env('SECURITY_PERMISSIONS_POLICY_ENABLED', true),
            'policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure audit logging settings for tracking admin activities
    | and security events throughout the application.
    |
    */

    'audit_logging' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_channel' => env('AUDIT_LOG_CHANNEL', 'daily'),
        'log_requests' => env('AUDIT_LOG_REQUESTS', true),
        'log_responses' => env('AUDIT_LOG_RESPONSES', false),
        'log_query_params' => env('AUDIT_LOG_QUERY_PARAMS', true),
        'log_request_body' => env('AUDIT_LOG_REQUEST_BODY', false),
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'private_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting thresholds for different types of operations.
    | These settings work in conjunction with the RateLimitServiceProvider.
    |
    */

    'rate_limits' => [
        'admin_auth' => [
            'per_minute' => env('RATE_LIMIT_AUTH_PER_MINUTE', 5),
            'per_hour' => env('RATE_LIMIT_AUTH_PER_HOUR', 20),
            'per_day' => env('RATE_LIMIT_AUTH_PER_DAY', 50),
        ],

        'admin_api' => [
            'per_minute' => env('RATE_LIMIT_API_PER_MINUTE', 120),
            'per_hour' => env('RATE_LIMIT_API_PER_HOUR', 2000),
            'per_day' => env('RATE_LIMIT_API_PER_DAY', 10000),
        ],

        'file_upload' => [
            'per_minute' => env('RATE_LIMIT_UPLOAD_PER_MINUTE', 10),
            'per_hour' => env('RATE_LIMIT_UPLOAD_PER_HOUR', 50),
            'per_day' => env('RATE_LIMIT_UPLOAD_PER_DAY', 200),
        ],

        'bulk_operations' => [
            'per_minute' => env('RATE_LIMIT_BULK_PER_MINUTE', 5),
            'per_hour' => env('RATE_LIMIT_BULK_PER_HOUR', 20),
            'per_day' => env('RATE_LIMIT_BULK_PER_DAY', 50),
        ],

        'system_operations' => [
            'per_minute' => env('RATE_LIMIT_SYSTEM_PER_MINUTE', 2),
            'per_hour' => env('RATE_LIMIT_SYSTEM_PER_HOUR', 5),
            'per_day' => env('RATE_LIMIT_SYSTEM_PER_DAY', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings for admin authentication.
    |
    */

    'session' => [
        'timeout' => env('ADMIN_SESSION_TIMEOUT', 480), // 8 hours in minutes
        'concurrent_sessions' => env('ADMIN_CONCURRENT_SESSIONS', 3),
        'force_logout_on_ip_change' => env('ADMIN_FORCE_LOGOUT_IP_CHANGE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configure security settings for file uploads.
    |
    */

    'file_upload' => [
        'max_size' => env('MAX_UPLOAD_SIZE', 5242880), // 5MB in bytes
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ],
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
        ],
        'scan_for_malware' => env('SCAN_UPLOADS_FOR_MALWARE', false),
        'quarantine_suspicious' => env('QUARANTINE_SUSPICIOUS_UPLOADS', true),
    ],

];
