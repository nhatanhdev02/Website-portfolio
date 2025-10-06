<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register production-specific services
        if ($this->app->environment('production')) {
            $this->registerProductionServices();
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            $this->configureProductionSettings();
            $this->configureSSL();
            $this->configureDatabaseOptimizations();
            $this->configureQueryLogging();
        }
    }

    /**
     * Register production-specific services.
     */
    private function registerProductionServices(): void
    {
        // Register New Relic if available
        if (config('production.monitoring.apm.new_relic.license_key')) {
            $this->app->singleton('newrelic', function () {
                return extension_loaded('newrelic');
            });
        }

        // Register Sentry if available
        if (config('production.monitoring.error_tracking.sentry.dsn')) {
            $this->app->register(\Sentry\Laravel\ServiceProvider::class);
        }
    }

    /**
     * Configure production settings.
     */
    private function configureProductionSettings(): void
    {
        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Configure trusted proxies
        if (config('production.ssl.trusted_proxies')) {
            $this->app['request']->setTrustedProxies(
                ['*'], // Trust all proxies in production behind load balancer
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }
    }

    /**
     * Configure SSL settings.
     */
    private function configureSSL(): void
    {
        if (config('production.ssl.force_https')) {
            URL::forceScheme('https');
        }

        // Configure secure proxy SSL header
        if (config('production.ssl.secure_proxy_ssl_header')) {
            $this->app['request']->setTrustedHeaderName(
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
                config('production.ssl.secure_proxy_ssl_header')
            );
        }
    }

    /**
     * Configure database optimizations.
     */
    private function configureDatabaseOptimizations(): void
    {
        // Set MySQL connection options for production
        if (config('database.default') === 'mysql') {
            DB::connection()->getPdo()->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            DB::connection()->getPdo()->exec("SET SESSION innodb_lock_wait_timeout=50");
            DB::connection()->getPdo()->exec("SET SESSION lock_wait_timeout=60");
        }
    }

    /**
     * Configure query logging for production monitoring.
     */
    private function configureQueryLogging(): void
    {
        // Log slow queries in production
        DB::listen(function (QueryExecuted $query) {
            $threshold = config('production.performance.query_cache.ttl', 100);

            if ($query->time > $threshold) {
                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);

                // Report to New Relic if available
                if (extension_loaded('newrelic')) {
                    newrelic_record_custom_event('SlowQuery', [
                        'query_time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            }
        });
    }
}
