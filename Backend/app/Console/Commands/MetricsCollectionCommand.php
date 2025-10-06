<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\MonitoringService;
use App\Services\Admin\AlertingService;
use Illuminate\Support\Facades\Log;

class MetricsCollectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:metrics:collect
                            {--output=log : Output destination (log, console, both)}
                            {--cleanup : Clean up old metrics data}
                            {--check-thresholds : Check metrics against alert thresholds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect and record system metrics for monitoring with optional alerting';

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
        $output = $this->option('output');
        $cleanup = $this->option('cleanup');
        $checkThresholds = $this->option('check-thresholds');

        try {
            // Clean up old metrics if requested
            if ($cleanup) {
                $this->info('🧹 Cleaning up old metrics data...');
                $this->monitoringService->clearOldMetrics();
                $this->alertingService->clearOldAlertHistory();
                $this->info('✅ Old metrics data cleaned up');
            }

            // Collect current metrics
            $this->info('📊 Collecting system metrics...');
            $metrics = $this->monitoringService->getSystemMetrics();

            // Check thresholds and send alerts if needed
            if ($checkThresholds) {
                $this->checkMetricThresholds($metrics);
            }

            // Output metrics based on option
            if ($output === 'console' || $output === 'both') {
                $this->displayMetrics($metrics);
            }

            if ($output === 'log' || $output === 'both') {
                Log::info('Metrics Collection', $metrics);
            }

            $this->info('✅ Metrics collection completed successfully');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Metrics collection failed: ' . $e->getMessage());
            Log::error('Metrics collection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Display metrics in console format.
     */
    private function displayMetrics(array $metrics): void
    {
        $this->newLine();
        $this->info('📈 System Metrics - ' . $metrics['timestamp']);
        $this->newLine();

        // Memory Usage
        $memory = $metrics['memory_usage'];
        $this->line("💾 Memory Usage:");
        $this->line("  Current: {$memory['current_usage_mb']}MB");
        $this->line("  Peak: {$memory['peak_usage_mb']}MB");
        $this->line("  Limit: {$memory['limit']}");
        $this->newLine();

        // Database Metrics
        $database = $metrics['database_metrics'];
        $this->line("🗄️  Database:");
        if (isset($database['error'])) {
            $this->error("  Status: Error - " . $database['error']);
        } else {
            $this->line("  Connection: {$database['connection']}");
            $this->line("  Query Response Time: {$database['query_response_time']}ms");
            $this->line("  Active Connections: {$database['active_connections']}");
        }
        $this->newLine();

        // Cache Metrics
        $cache = $metrics['cache_metrics'];
        $this->line("🚀 Cache:");
        if (isset($cache['error'])) {
            $this->error("  Status: Error - " . $cache['error']);
        } else {
            $this->line("  Driver: {$cache['driver']}");
            $this->line("  Response Time: {$cache['response_time']}ms");

            if (isset($cache['redis_info'])) {
                $redis = $cache['redis_info'];
                $this->line("  Redis Connected Clients: " . ($redis['connected_clients'] ?? 'N/A'));
                $this->line("  Redis Memory Usage: " . ($redis['used_memory_human'] ?? 'N/A'));
            }
        }
        $this->newLine();

        // Queue Metrics
        $queue = $metrics['queue_metrics'];
        $this->line("📋 Queue:");
        if (isset($queue['error'])) {
            $this->error("  Status: Error - " . $queue['error']);
        } else {
            $this->line("  Driver: {$queue['driver']}");
            $this->line("  Connection: {$queue['connection']}");

            if (isset($queue['pending_jobs'])) {
                $this->line("  Pending Jobs: {$queue['pending_jobs']}");
            }
            if (isset($queue['failed_jobs'])) {
                $this->line("  Failed Jobs: {$queue['failed_jobs']}");
            }
            if (isset($queue['queue_length'])) {
                $this->line("  Queue Length: {$queue['queue_length']}");
            }
        }
        $this->newLine();

        // Disk Usage
        $disk = $metrics['disk_usage'];
        $this->line("💽 Disk Usage:");
        if (isset($disk['error'])) {
            $this->error("  Status: Error - " . $disk['error']);
        } else {
            $this->line("  Usage: {$disk['usage_percentage']}%");
            $this->line("  Free Space: {$disk['free_mb']}MB");
            $this->line("  Total Space: {$disk['total_mb']}MB");

            // Warning for high disk usage
            if ($disk['usage_percentage'] > 80) {
                $this->warn("  ⚠️  Warning: High disk usage detected!");
            }
        }
        $this->newLine();
    }

    /**
     * Check metrics against alert thresholds.
     */
    private function checkMetricThresholds(array $metrics): void
    {
        $thresholds = config('monitoring.alerts', []);

        // Check memory usage
        if (isset($metrics['memory_usage']['current_usage_mb'])) {
            $memoryUsage = $metrics['memory_usage']['current_usage_mb'];
            $threshold = $thresholds['memory_usage_threshold'] ?? 500;

            if ($memoryUsage > $threshold) {
                $this->alertingService->sendPerformanceAlert(
                    'memory_usage',
                    $memoryUsage,
                    $threshold,
                    'MB'
                );
            }
        }

        // Check disk usage
        if (isset($metrics['disk_usage']['usage_percentage'])) {
            $diskUsage = $metrics['disk_usage']['usage_percentage'];
            $threshold = $thresholds['disk_usage_threshold'] ?? 90;

            if ($diskUsage > $threshold) {
                $this->alertingService->sendDiskSpaceAlert(
                    $diskUsage,
                    $metrics['disk_usage']['storage_path'] ?? 'unknown'
                );
            }
        }

        // Check database response time
        if (isset($metrics['database_metrics']['query_response_time'])) {
            $responseTime = $metrics['database_metrics']['query_response_time'];
            $threshold = $thresholds['database_response_threshold'] ?? 100;

            if ($responseTime > $threshold) {
                $this->alertingService->sendPerformanceAlert(
                    'database_response_time',
                    $responseTime,
                    $threshold,
                    'ms'
                );
            }
        }

        // Check cache response time
        if (isset($metrics['cache_metrics']['response_time'])) {
            $responseTime = $metrics['cache_metrics']['response_time'];
            $threshold = $thresholds['cache_response_threshold'] ?? 50;

            if ($responseTime > $threshold) {
                $this->alertingService->sendPerformanceAlert(
                    'cache_response_time',
                    $responseTime,
                    $threshold,
                    'ms'
                );
            }
        }

        // Check for system errors
        if (isset($metrics['database_metrics']['error']) ||
            isset($metrics['cache_metrics']['error']) ||
            isset($metrics['queue_metrics']['error']) ||
            isset($metrics['disk_usage']['error'])) {

            $errors = [];
            if (isset($metrics['database_metrics']['error'])) {
                $errors['database'] = $metrics['database_metrics']['error'];
            }
            if (isset($metrics['cache_metrics']['error'])) {
                $errors['cache'] = $metrics['cache_metrics']['error'];
            }
            if (isset($metrics['queue_metrics']['error'])) {
                $errors['queue'] = $metrics['queue_metrics']['error'];
            }
            if (isset($metrics['disk_usage']['error'])) {
                $errors['disk'] = $metrics['disk_usage']['error'];
            }

            foreach ($errors as $component => $error) {
                $this->alertingService->sendAlert(
                    "system_error_{$component}",
                    "System component error detected in {$component}: {$error}",
                    ['component' => $component, 'error' => $error],
                    'critical'
                );
            }
        }
    }
}
