<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\AuditLogService;

class CleanAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:clean {--days=90 : Number of days to keep audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old audit logs to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle(AuditLogService $auditLogService): int
    {
        $daysToKeep = (int) $this->option('days');

        if ($daysToKeep < 1) {
            $this->error('Days to keep must be at least 1');
            return self::FAILURE;
        }

        $this->info("Cleaning audit logs older than {$daysToKeep} days...");

        try {
            $deletedCount = $auditLogService->cleanOldLogs($daysToKeep);

            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} old audit log records.");
            } else {
                $this->info('No old audit logs found to delete.');
            }

            // Show current statistics
            $stats = $auditLogService->getAuditStatistics('30d');
            $this->info("Current audit log statistics (last 30 days):");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Events', $stats['total_events']],
                    ['Auth Events', $stats['auth_events']],
                    ['CRUD Operations', $stats['crud_operations']],
                    ['Security Events', $stats['security_events']],
                    ['Error Events', $stats['error_events']],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clean audit logs: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
