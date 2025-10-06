<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Http\Request;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:health:check
                            {--component= : Check specific component (database, cache, storage, queue)}
                            {--format=table : Output format (table, json)}
                            {--fail-on-warning : Exit with error code on warnings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform health checks on the admin backend system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Running Admin Backend Health Checks...');
        $this->newLine();

        $controller = new HealthCheckController();
        $request = new Request();

        // Add secret for production
        if (app()->environment('production')) {
            $request->merge(['secret' => config('production.monitoring.health_checks.secret')]);
        }

        $component = $this->option('component');
        $format = $this->option('format');

        try {
            if ($component) {
                $result = $this->checkSpecificComponent($controller, $component, $request);
            } else {
                $response = $controller->index($request);
                $result = json_decode($response->getContent(), true);
            }

            if ($format === 'json') {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return $this->getExitCode($result);
            }

            $this->displayResults($result, $component);
            return $this->getExitCode($result);

        } catch (\Exception $e) {
            $this->error('❌ Health check failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check a specific component.
     */
    private function checkSpecificComponent(HealthCheckController $controller, string $component, Request $request): array
    {
        switch ($component) {
            case 'database':
                $response = $controller->database();
                break;
            case 'cache':
                $response = $controller->cache();
                break;
            default:
                $response = $controller->index($request);
                break;
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * Display health check results in table format.
     */
    private function displayResults(array $result, ?string $component = null): void
    {
        if ($component) {
            $this->displayComponentResult($result);
            return;
        }

        // Overall status
        $statusIcon = $this->getStatusIcon($result['status']);
        $this->info("Overall Status: {$statusIcon} " . strtoupper($result['status']));
        $this->info("Environment: " . $result['environment']);
        $this->info("Timestamp: " . $result['timestamp']);
        $this->newLine();

        // Individual checks
        $tableData = [];
        foreach ($result['checks'] as $checkName => $checkResult) {
            $tableData[] = [
                'Component' => ucfirst($checkName),
                'Status' => $this->getStatusIcon($checkResult['status']) . ' ' . strtoupper($checkResult['status']),
                'Message' => $checkResult['message'],
                'Response Time' => isset($checkResult['response_time']) ? $checkResult['response_time'] . 'ms' : 'N/A',
            ];
        }

        $this->table(['Component', 'Status', 'Message', 'Response Time'], $tableData);

        // Summary
        $this->newLine();
        $this->info("Summary: " . $result['summary']);
    }

    /**
     * Display single component result.
     */
    private function displayComponentResult(array $result): void
    {
        $details = $result['details'] ?? $result;
        $statusIcon = $this->getStatusIcon($result['status']);

        $this->info("Component Status: {$statusIcon} " . strtoupper($result['status']));
        $this->info("Timestamp: " . $result['timestamp']);
        $this->newLine();

        $tableData = [];
        foreach ($details as $key => $value) {
            if ($key === 'status') continue;

            $tableData[] = [
                'Property' => ucfirst(str_replace('_', ' ', $key)),
                'Value' => is_array($value) ? json_encode($value) : (string)$value,
            ];
        }

        $this->table(['Property', 'Value'], $tableData);
    }

    /**
     * Get status icon for display.
     */
    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'unhealthy' => '❌',
            default => '❓',
        };
    }

    /**
     * Get appropriate exit code based on health status.
     */
    private function getExitCode(array $result): int
    {
        $status = $result['status'] ?? 'unhealthy';

        if ($status === 'unhealthy') {
            return 1;
        }

        if ($status === 'warning' && $this->option('fail-on-warning')) {
            return 1;
        }

        return 0;
    }
}
