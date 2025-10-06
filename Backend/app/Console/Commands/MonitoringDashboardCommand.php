<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\MonitoringService;
use App\Services\Admin\AlertingService;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitoringDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:monitoring:dashboard
                            {--refresh : Refresh cached data}
                            {--alerts : Show recent alerts}
                            {--detailed : Show detailed metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display comprehensive monitoring dashboard in console';

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
        $refresh = $this->option('refresh');
        $showAlerts = $this->option('alerts');
        $detailed = $this->option('detailed');

        try {
            $this->displayHeader();

            // Get system metrics
            $metrics = $this->monitoringService->getSystemMetrics();

            // Get health status
            $healthStatus = $this->getHealthStatus($refresh);

            // Display main dashboard
            $this->displayHealthStatus($healthStatus);
            $this->displaySystemMetrics($metrics, $detailed);

            // Display alerts if requested
            if ($showAlerts) {
                $this->displayRecentAlerts();
            }

            $this->displayFooter();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to generate monitoring dashboard: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display dashboard header.
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════════════════════╗');
        $this->info('║                          ADMIN BACKEND MONITORING DASHBOARD                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('🕐 Generated at: ' . now()->format('Y-m-d H:i:s T'));
        $this->info('🌍 Environment: ' . app()->environment());
        $this->info('🖥️  Server: ' . gethostname());
        $this->newLine();
    }

    /**
     * Get health status.
     */
    private function getHealthStatus(bool $refresh = false): array
    {
        $cacheKey = 'dashboard_health_status';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () {
            try {
                $healthController = new HealthCheckController();
                $request = new Request();

                if (app()->environment('production')) {
                    $request->merge(['secret' => config('monitoring.health_checks.secret')]);
                }

                $response = $healthController->index($request);
                return json_decode($response->getContent(), true);

            } catch (\Exception $e) {
                return [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Display health status.
     */
    private function displayHealthStatus(array $healthStatus): void
    {
        $this->info('🏥 SYSTEM HEALTH STATUS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $statusIcon = match ($healthStatus['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'unhealthy' => '❌',
            default => '❓',
        };

        $this->info("Overall Status: {$statusIcon} " . strtoupper($healthStatus['status']));

        if (isset($healthStatus['checks'])) {
            $this->newLine();
            $this->info('Component Status:');

            foreach ($healthStatus['checks'] as $component => $check) {
                $componentIcon = match ($check['status']) {
                    'healthy' => '✅',
                    'warning' => '⚠️',
                    'unhealthy' => '❌',
                    default => '❓',
                };

                $responseTime = isset($check['response_time']) ? " ({$check['response_time']}ms)" : '';
                $this->info("  {$componentIcon} " . ucfirst($component) . ": {$check['status']}{$responseTime}");

                if ($check['status'] !== 'healthy' && isset($check['message'])) {
                    $this->warn("    └─ {$check['message']}");
                }
            }
        }

        if (isset($healthStatus['error'])) {
            $this->error("Error: {$healthStatus['error']}");
        }

        $this->newLine();
    }

    /**
     * Display system metrics.
     */
    private function displaySystemMetrics(array $metrics, bool $detailed = false): void
    {
        $this->info('📊 SYSTEM METRICS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Memory Usage
        $memory = $metrics['memory_usage'];
        $memoryIcon = $memory['current_usage_mb'] > 500 ? '⚠️' : '✅';
        $this->info("{$memoryIcon} Memory Usage: {$memory['current_usage_mb']}MB current, {$memory['peak_usage_mb']}MB peak");

        if ($detailed) {
            $this->info("    └─ Limit: {$memory['limit']}");
        }

        // Database Metrics
        $database = $metrics['database_metrics'];
        if (isset($database['error'])) {
            $this->error("❌ Database: {$database['error']}");
        } else {
            $dbIcon = $database['query_response_time'] > 100 ? '⚠️' : '✅';
            $this->info("{$dbIcon} Database: {$database['query_response_time']}ms response time");

            if ($detailed) {
                $this->info("    └─ Connection: {$database['connection']}");
                $this->info("    └─ Active Connections: {$database['active_connections']}");
            }
        }

        // Cache Metrics
        $cache = $metrics['cache_metrics'];
        if (isset($cache['error'])) {
            $this->error("❌ Cache: {$cache['error']}");
        } else {
            $cacheIcon = $cache['response_time'] > 50 ? '⚠️' : '✅';
            $this->info("{$cacheIcon} Cache ({$cache['driver']}): {$cache['response_time']}ms response time");

            if ($detailed && isset($cache['redis_info'])) {
                $redis = $cache['redis_info'];
                $this->info("    └─ Redis Clients: " . ($redis['connected_clients'] ?? 'N/A'));
                $this->info("    └─ Redis Memory: " . ($redis['used_memory_human'] ?? 'N/A'));
            }
        }

        // Queue Metrics
        $queue = $metrics['queue_metrics'];
        if (isset($queue['error'])) {
            $this->error("❌ Queue: {$queue['error']}");
        } else {
            $this->info("✅ Queue ({$queue['driver']}): {$queue['connection']}");

            if ($detailed) {
                if (isset($queue['pending_jobs'])) {
                    $this->info("    └─ Pending Jobs: {$queue['pending_jobs']}");
                }
                if (isset($queue['failed_jobs'])) {
                    $this->info("    └─ Failed Jobs: {$queue['failed_jobs']}");
                }
            }
        }

        // Disk Usage
        $disk = $metrics['disk_usage'];
        if (isset($disk['error'])) {
            $this->error("❌ Disk: {$disk['error']}");
        } else {
            $diskIcon = $disk['usage_percentage'] > 90 ? '❌' : ($disk['usage_percentage'] > 80 ? '⚠️' : '✅');
            $this->info("{$diskIcon} Disk Usage: {$disk['usage_percentage']}% ({$disk['free_mb']}MB free)");

            if ($detailed) {
                $this->info("    └─ Total Space: {$disk['total_mb']}MB");
                $this->info("    └─ Path: {$disk['storage_path']}");
            }
        }

        $this->newLine();
    }

    /**
     * Display recent alerts.
     */
    private function displayRecentAlerts(): void
    {
        $this->info('🚨 RECENT ALERTS (Last 24 hours)');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $alerts = $this->alertingService->getAlertHistory(1);

            if (empty($alerts)) {
                $this->info('✅ No alerts in the last 24 hours');
            } else {
                $totalAlerts = 0;
                foreach ($alerts as $date => $dayAlerts) {
                    $totalAlerts += count($dayAlerts);

                    foreach ($dayAlerts as $alert) {
                        $severityIcon = match ($alert['severity']) {
                            'critical' => '🔴',
                            'warning' => '🟡',
                            'info' => '🔵',
                            default => '⚪',
                        };

                        $time = \Carbon\Carbon::parse($alert['timestamp'])->format('H:i:s');
                        $this->info("{$severityIcon} [{$time}] {$alert['type']}: {$alert['message']}");
                    }
                }

                $this->newLine();
                $this->info("Total alerts: {$totalAlerts}");
            }

        } catch (\Exception $e) {
            $this->error("Failed to retrieve alerts: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Display dashboard footer.
     */
    private function displayFooter(): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('💡 Commands:');
        $this->info('   • php artisan admin:health:check - Run health checks');
        $this->info('   • php artisan admin:metrics:collect - Collect system metrics');
        $this->info('   • php artisan admin:alert:test <type> - Test alert system');
        $this->info('   • php artisan admin:monitor:system - Start continuous monitoring');
        $this->newLine();
    }
}
