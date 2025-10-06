<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Cache Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains cache configuration specific to admin operations.
    | It defines cache TTL values for different content types and cache keys.
    |
    */

    'ttl' => [
        'default' => env('REDIS_CACHE_TTL', 3600), // 1 hour
        'hero' => env('REDIS_CACHE_HERO_TTL', 7200), // 2 hours
        'about' => env('REDIS_CACHE_ABOUT_TTL', 7200), // 2 hours
        'services' => env('REDIS_CACHE_SERVICES_TTL', 1800), // 30 minutes
        'projects' => env('REDIS_CACHE_PROJECTS_TTL', 1800), // 30 minutes
        'blog' => env('REDIS_CACHE_BLOG_TTL', 1800), // 30 minutes
        'settings' => env('REDIS_CACHE_SETTINGS_TTL', 3600), // 1 hour
        'contact' => env('REDIS_CACHE_CONTACT_TTL', 600), // 10 minutes
    ],

    'keys' => [
        'hero_content' => 'admin:hero:content',
        'about_content' => 'admin:about:content',
        'services_list' => 'admin:services:list',
        'services_ordered' => 'admin:services:ordered',
        'projects_list' => 'admin:projects:list',
        'projects_featured' => 'admin:projects:featured',
        'blog_posts' => 'admin:blog:posts',
        'blog_published' => 'admin:blog:published',
        'settings_all' => 'admin:settings:all',
        'contact_info' => 'admin:contact:info',
        'contact_messages_unread' => 'admin:contact:messages:unread',
    ],

    'tags' => [
        'hero' => ['admin', 'hero'],
        'about' => ['admin', 'about'],
        'services' => ['admin', 'services'],
        'projects' => ['admin', 'projects'],
        'blog' => ['admin', 'blog'],
        'settings' => ['admin', 'settings'],
        'contact' => ['admin', 'contact'],
    ],

    'warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        'schedule' => env('CACHE_WARMING_SCHEDULE', '0 */6 * * *'), // Every 6 hours
        'keys' => [
            'hero_content',
            'about_content',
            'services_ordered',
            'projects_featured',
            'settings_all',
            'contact_info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for database query performance monitoring
    |
    */
    'query_monitoring' => env('QUERY_MONITORING_ENABLED', false),
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100), // milliseconds
    'high_query_count_threshold' => env('HIGH_QUERY_COUNT_THRESHOLD', 20),

];
