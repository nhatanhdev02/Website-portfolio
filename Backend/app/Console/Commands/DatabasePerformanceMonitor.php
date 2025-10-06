<?php

namespace App\Console\Commands;

use App\Services\Admin\DatabaseOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:db:monitor
                            {--enable-logging : Enable query logging during monitoring}
                            {--analyze-tables : Analyze table sizes and suggest optimizations}
                            {--analyze-indexes : Analyze index usage}
                            {--optimize-tables : Optimize database tables}
                            {--stats : Show performance statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database performance and provide optimization suggestions';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseOptimizationService $dbOptimization): int
    {
        $this->info('Database Performance Monitor');
        $this->line('============================');

        if ($this->option('enable-logging')) {
            $dbOptimization->enableQueryLogging();
            $this->info('✓ Query logging enabled');
        }

        if ($this->option('stats')) {
            $this->showPerformanceStats($dbOptimization);
        }

        if ($this->option('analyze-tables')) {
            $this->analyzeTableSizes($dbOptimization);
        }

        if ($this->option('analyze-indexes')) {
            $this->analyzeIndexUsage($dbOptimization);
        }

        if ($this->option('optimize-tables')) {
            $this->optimizeTables($dbOptimization);
        }

        // If no specific options, show general database info
        if (!$this->hasOptions()) {
            $this->showGeneralInfo();
        }

        return Command::SUCCESS;
    }

    /**
     * Show performance statistics
     */
    private function showPerformanceStats(DatabaseOptimizationService $dbOptimization): void
    {
        $this->newLine();
        $this->info('Performance Statistics:');

        $stats = $dbOptimization->getPerformanceStats();

        if ($stats['total_queries'] === 0) {
            $this->line('No queries logged. Enable query logging first with --enable-logging');
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $stats['total_queries']],
                ['Total Time', $stats['formatted_total_time']],
                ['Average Time', $stats['formatted_average_time']],
                ['Slow Queries', $stats['slow_queries']],
                ['Slow Query %', $stats['slow_query_percentage'] . '%'],
                ['Fastest Query', $stats['fastest_query'] . 'ms'],
                ['Slowest Query', $stats['slowest_query'] . 'ms'],
            ]
        );

        if ($stats['slow_queries'] > 0) {
            $this->warn("⚠ {$stats['slow_queries']} slow queries detected (>{$stats['slow_query_percentage']}%)");
        }
    }

    /**
     * Analyze table sizes
     */
    private function analyzeTableSizes(DatabaseOptimizationService $dbOptimization): void
    {
        $this->newLine();
        $this->info('Table Size Analysis:');

        $analysis = $dbOptimization->analyzeTableSizes();

        $tableData = [];
        foreach ($analysis as $table => $data) {
            if (isset($data['error'])) {
                $tableData[] = [$table, 'Error', $data['error'], ''];
            } else {
                $suggestions = implode('; ', $data['optimization_suggestions']);
                $tableData[] = [
                    $table,
                    number_format($data['row_count']),
                    $data['size_mb'] . ' MB',
                    $suggestions ?: 'No suggestions'
                ];
            }
        }

        $this->table(
            ['Table', 'Rows', 'Size', 'Suggestions'],
            $tableData
        );
    }

    /**
     * Analyze index usage
     */
    private function analyzeIndexUsage(DatabaseOptimizationService $dbOptimization): void
    {
        $this->newLine();
        $this->info('Index Usage Analysis:');

        $analysis = $dbOptimization->analyzeIndexUsage();

        if (isset($analysis['error'])) {
            $this->error($analysis['error']);
            return;
        }

        foreach ($analysis as $table => $indexes) {
            $this->line("Table: {$table}");

            $indexData = [];
            foreach ($indexes as $index) {
                $indexData[] = [
                    $index['index_name'],
                    $index['column_name'],
                    $index['cardinality'] ?? 'N/A',
                    $index['effectiveness']
                ];
            }

            $this->table(
                ['Index', 'Column', 'Cardinality', 'Effectiveness'],
                $indexData
            );
            $this->newLine();
        }
    }

    /**
     * Optimize database tables
     */
    private function optimizeTables(DatabaseOptimizationService $dbOptimization): void
    {
        $this->newLine();
        $this->info('Optimizing Database Tables...');

        if (!$this->confirm('This will optimize all admin tables. Continue?')) {
            $this->line('Operation cancelled.');
            return;
        }

        $results = $dbOptimization->optimizeTables();

        foreach ($results as $table => $result) {
            if (str_contains($result, 'successfully')) {
                $this->line("✓ {$table}: {$result}");
            } else {
                $this->error("✗ {$table}: {$result}");
            }
        }
    }

    /**
     * Show general database information
     */
    private function showGeneralInfo(): void
    {
        $this->newLine();
        $this->info('Database Information:');

        try {
            $connection = DB::connection();
            $this->line('Connection: ' . $connection->getName());
            $this->line('Driver: ' . $connection->getDriverName());

            // Get database size (MySQL specific)
            if ($connection->getDriverName() === 'mysql') {
                $size = DB::select("
                    SELECT
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                ");

                if (!empty($size)) {
                    $this->line('Database Size: ' . $size[0]->size_mb . ' MB');
                }
            }

        } catch (\Exception $e) {
            $this->error('Could not retrieve database information: ' . $e->getMessage());
        }

        $this->newLine();
        $this->line('Available options:');
        $this->line('  --enable-logging    Enable query logging');
        $this->line('  --stats            Show performance statistics');
        $this->line('  --analyze-tables   Analyze table sizes');
        $this->line('  --analyze-indexes  Analyze index usage');
        $this->line('  --optimize-tables  Optimize database tables');
    }

    /**
     * Check if any options are provided
     */
    private function hasOptions(): bool
    {
        return $this->option('enable-logging') ||
               $this->option('stats') ||
               $this->option('analyze-tables') ||
               $this->option('analyze-indexes') ||
               $this->option('optimize-tables');
    }
}
