<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\MonitoringService;
use App\Services\Admin\AlertingService;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SystemMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:monitor:system
                            {--interval=60 : Monitoring interval in seconds}
                            {--duration=3600 : Total monitoring duration in seconds}
                            {--output=log : Output destination (log, console, both)}
                            {--health-checks : Include health checks in monitoring}
                            {--alert-on-issues : Send alerts when issues are detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system performance, health, and send alerts when issues are detected';

    private MonitoringService $monitoringService;
    private AlertingService $alertingService;

    public function __construct(MonitoringService $monitoringService, AlertingService $alertingService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->alertingService = $alertingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        $duration = (int) $this->option('duration');
        $output = $this->option('output');
        $includeHealthChecks = $this->option('health-checks');
        $alertOnIssues = $this->option('alert-on-issues');

        $this->info("🔍 Starting comprehensive system monitoring...");
        $this->info("Interval: {$interval} seconds");
        $this->info("Duration: {$duration} seconds");
        $this->info("Health checks: " . ($includeHealthChecks ? 'enabled' : 'disabled'));
        $this->info("Alerting: " . ($alertOnIssues ? 'enabled' : 'disabled'));
        $this->newLine();

        $startTime = time();
        $endTime = $startTime + $duration;
        $iterations = 0;

        while (time() < $endTime) {
            $iterations++;

            try {
                // Get system metrics
                $metrics = $this->monitoringService->getSystemMetrics();

                // Perform health checks if requested
                $healthStatus = null;
                if ($includeHealthChecks) {
                    $healthStatus = $this->performHealthChecks();
                }

                // Output metrics based on option
                if ($output === 'console' || $output === 'both') {
                    $this->displayMetrics($metrics, $iterations);
                    if ($healthStatus) {
                        $this->displayHealthStatus($healthStatus);
                    }
                }

                if ($output === 'log' || $output === 'both') {
                    Log::info('System Monitoring Metrics', $metrics);
                    if ($healthStatus) {
                        Log::info('Health Check Results', $healthStatus);
                    }
                }

                // Check for alerts and send if enabled
                if ($alertOnIssues) {
                    $this->checkAndSendAlerts($metrics, $healthStatus);
                } else {
                    $this->checkAlerts($metrics);
                }

            } catch (\Exception $e) {
                $this->error("❌ Error during monitoring: {$e->getMessage()}");
                Log::error('System monitoring error', [
                    'error' => $e->getMessage(),
                    'iteration' => $iterations,
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($alertOnIssues) {
                    $this->alertingService->sendAlert(
                        'monitoring_error',
                        "System monitoring failed: {$e->getMessage()}",
                        ['iteration' => $iterations, 'error' => $e->getMessage()],
                        'critical'
                    );
                }
            }

            // Wait for next interval
            if (time() < $endTime) {
                sleep($interval);
            }
        }

        $this->info("✅ System monitoring completed after {$iterations} iterations");
        return 0;
    }

    /**
     * Display metrics in console.
     */
    private function displayMetrics(array $metrics, int $iteration): void
    {
        $this->info("📊 Metrics Collection #{$iteration} - " . $metrics['timestamp']);

        // Memory metrics
        $memory = $metrics['memory_usage'];
        $this->line("Memory: {$memory['current_usage_mb']}MB current, {$memory['peak_usage_mb']}MB peak");

        // Database metrics
        $database = $metrics['database_metrics'];
        if (!isset($database['error'])) {
            $this->line("Database: {$database['query_response_time']}ms response time");
        } else {
            $this->error("Database: " . $database['error']);
        }

        // Cache metrics
        $cache = $metrics['cache_metrics'];
        if (!isset($cache['error'])) {
            $this->line("Cache ({$cache['driver']}): {$cache['response_time']}ms response time");
        } else {
            $this->error("Cache: " . $cache['error']);
        }

        // Disk usage
        $disk = $metrics['disk_usage'];
        if (!isset($disk['error'])) {
            $this->line("Disk: {$disk['usage_percentage']}% used ({$disk['free_mb']}MB free)");
        } else {
            $this->error("Disk: " . $disk['error']);
        }

        $this->newLine();
    }

    /**
     * Perform health checks.
     */
    private function performHealthChecks(): array
    {
        try {
            $healthController = new HealthCheckController();
            $request = new Request();

            // Add secret for production
            if (app()->environment('production')) {
                $request->merge(['secret' => config('monitoring.health_checks.secret')]);
            }

            $response = $healthController->index($request);
            return json_decode($response->getContent(), true);

        } catch (\Exception $e) {
            Log::error('Health check failed during monitoring', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Display health status in console.
     */
    private function displayHealthStatus(array $healthStatus): void
    {
        $statusIcon = match ($healthStatus['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'unhealthy' => '❌',
            default => '❓',
        };

        $this->line("Health Status: {$statusIcon} " . strtoupper($healthStatus['status']));

        if (isset($healthStatus['checks'])) {
            foreach ($healthStatus['checks'] as $component => $check) {
                $componentIcon = match ($check['status']) {
                    'healthy' => '✅',
                    'warning' => '⚠️',
                    'unhealthy' => '❌',
                    default => '❓',
                };
                $this->line("  {$component}: {$componentIcon} {$check['status']}");
            }
        }
    }

    /**
     * Check for alert conditions and send alerts.
     */
    private function checkAndSendAlerts(array $metrics, ?array $healthStatus = null): void
    {
        $thresholds = config('monitoring.alerts', []);

        // Memory usage alert
        $memoryUsage = $metrics['memory_usage']['current_usage_mb'];
        $memoryThreshold = $thresholds['memory_usage_threshold'] ?? 500;
        if ($memoryUsage > $memoryThreshold) {
            $this->alertingService->sendPerformanceAlert('memory_usage', $memoryUsage, $memoryThreshold, 'MB');
            $this->warn("⚠️  ALERT: High memory usage: {$memoryUsage}MB");
        }

        // Disk usage alert
        if (isset($metrics['disk_usage']['usage_percentage'])) {
            $diskUsage = $metrics['disk_usage']['usage_percentage'];
            $diskThreshold = $thresholds['disk_usage_threshold'] ?? 90;
            if ($diskUsage > $diskThreshold) {
                $this->alertingService->sendDiskSpaceAlert($diskUsage, $metrics['disk_usage']['storage_path'] ?? 'unknown');
                $this->warn("⚠️  ALERT: High disk usage: {$diskUsage}%");
            }
        }

        // Database response time alert
        if (isset($metrics['database_metrics']['query_response_time'])) {
            $dbResponseTime = $metrics['database_metrics']['query_response_time'];
            $dbThreshold = $thresholds['database_response_threshold'] ?? 100;
            if ($dbResponseTime > $dbThreshold) {
                $this->alertingService->sendPerformanceAlert('database_response_time', $dbResponseTime, $dbThreshold, 'ms');
                $this->warn("⚠️  ALERT: Slow database response: {$dbResponseTime}ms");
            }
        }

        // Cache response time alert
        if (isset($metrics['cache_metrics']['response_time'])) {
            $cacheResponseTime = $metrics['cache_metrics']['response_time'];
            $cacheThreshold = $thresholds['cache_response_threshold'] ?? 50;
            if ($cacheResponseTime > $cacheThreshold) {
                $this->alertingService->sendPerformanceAlert('cache_response_time', $cacheResponseTime, $cacheThreshold, 'ms');
                $this->warn("⚠️  ALERT: Slow cache response: {$cacheResponseTime}ms");
            }
        }

        // Health check alerts
        if ($healthStatus && $healthStatus['status'] !== 'healthy') {
            if (isset($healthStatus['checks'])) {
                foreach ($healthStatus['checks'] as $component => $check) {
                    if ($check['status'] !== 'healthy') {
                        $this->alertingService->sendHealthCheckAlert($component, $check);
                        $this->warn("⚠️  ALERT: Health check failed for {$component}: {$check['message']}");
                    }
                }
            }
        }

        // System error alerts
        $systemErrors = [];
        if (isset($metrics['database_metrics']['error'])) {
            $systemErrors['database'] = $metrics['database_metrics']['error'];
        }
        if (isset($metrics['cache_metrics']['error'])) {
            $systemErrors['cache'] = $metrics['cache_metrics']['error'];
        }
        if (isset($metrics['queue_metrics']['error'])) {
            $systemErrors['queue'] = $metrics['queue_metrics']['error'];
        }
        if (isset($metrics['disk_usage']['error'])) {
            $systemErrors['disk'] = $metrics['disk_usage']['error'];
        }

        foreach ($systemErrors as $component => $error) {
            $this->alertingService->sendAlert(
                "system_error_{$component}",
                "System component error in {$component}: {$error}",
                ['component' => $component, 'error' => $error],
                'critical'
            );
            $this->error("❌ CRITICAL: {$component} error: {$error}");
        }
    }

    /**
     * Check for alert conditions (console display only).
     */
    private function checkAlerts(array $metrics): void
    {
        $alerts = [];

        // Memory usage alert
        $memoryUsage = $metrics['memory_usage']['current_usage_mb'];
        if ($memoryUsage > 500) {
            $alerts[] = "High memory usage: {$memoryUsage}MB";
        }

        // Disk usage alert
        if (isset($metrics['disk_usage']['usage_percentage'])) {
            $diskUsage = $metrics['disk_usage']['usage_percentage'];
            if ($diskUsage > 90) {
                $alerts[] = "High disk usage: {$diskUsage}%";
            }
        }

        // Database response time alert
        if (isset($metrics['database_metrics']['query_response_time'])) {
            $dbResponseTime = $metrics['database_metrics']['query_response_time'];
            if ($dbResponseTime > 100) {
                $alerts[] = "Slow database response: {$dbResponseTime}ms";
            }
        }

        // Cache response time alert
        if (isset($metrics['cache_metrics']['response_time'])) {
            $cacheResponseTime = $metrics['cache_metrics']['response_time'];
            if ($cacheResponseTime > 50) {
                $alerts[] = "Slow cache response: {$cacheResponseTime}ms";
            }
        }

        // Log alerts
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->warn("⚠️  ALERT: {$alert}");
                Log::warning('System Alert', [
                    'alert' => $alert,
                    'metrics' => $metrics,
                ]);
            }
        }
    }
}
