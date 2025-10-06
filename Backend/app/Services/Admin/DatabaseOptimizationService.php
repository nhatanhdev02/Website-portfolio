<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DatabaseOptimizationService
{
    /**
     * Enable query logging for performance monitoring
     *
     * @return void
     */
    public function enableQueryLogging(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Disable query logging
     *
     * @return void
     */
    public function disableQueryLogging(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Get executed queries with performance metrics
     *
     * @return Collection
     */
    public function getQueryLog(): Collection
    {
        return collect(DB::getQueryLog())->map(function ($query) {
            return [
                'sql' => $query['query'],
                'bindings' => $query['bindings'],
                'time' => $query['time'],
                'formatted_time' => $this->formatQueryTime($query['time']),
                'is_slow' => $query['time'] > 100, // Mark queries over 100ms as slow
            ];
        });
    }

    /**
     * Log slow queries for monitoring
     *
     * @param float $threshold Threshold in milliseconds
     * @return void
     */
    public function logSlowQueries(float $threshold = 100): void
    {
        $queries = $this->getQueryLog();
        $slowQueries = $queries->filter(function ($query) use ($threshold) {
            return $query['time'] > $threshold;
        });

        if ($slowQueries->isNotEmpty()) {
            Log::warning('Slow queries detected', [
                'threshold_ms' => $threshold,
                'slow_query_count' => $slowQueries->count(),
                'total_queries' => $queries->count(),
                'slow_queries' => $slowQueries->toArray()
            ]);
        }
    }

    /**
     * Get database performance statistics
     *
     * @return array
     */
    public function getPerformanceStats(): array
    {
        $queries = $this->getQueryLog();

        if ($queries->isEmpty()) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'slow_queries' => 0,
                'fastest_query' => 0,
                'slowest_query' => 0
            ];
        }

        $totalTime = $queries->sum('time');
        $slowQueries = $queries->filter(fn($q) => $q['is_slow'])->count();

        return [
            'total_queries' => $queries->count(),
            'total_time' => $totalTime,
            'average_time' => round($totalTime / $queries->count(), 2),
            'slow_queries' => $slowQueries,
            'slow_query_percentage' => round(($slowQueries / $queries->count()) * 100, 2),
            'fastest_query' => $queries->min('time'),
            'slowest_query' => $queries->max('time'),
            'formatted_total_time' => $this->formatQueryTime($totalTime),
            'formatted_average_time' => $this->formatQueryTime($totalTime / $queries->count())
        ];
    }

    /**
     * Analyze table sizes and suggest optimizations
     *
     * @return array
     */
    public function analyzeTableSizes(): array
    {
        $tables = [
            'admins', 'heroes', 'about', 'services', 'projects',
            'blog_posts', 'contact_messages', 'contact_info', 'system_settings'
        ];

        $analysis = [];

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $size = $this->getTableSize($table);

                $analysis[$table] = [
                    'row_count' => $count,
                    'size_mb' => $size,
                    'avg_row_size' => $count > 0 ? round($size / $count * 1024 * 1024, 2) : 0,
                    'optimization_suggestions' => $this->getOptimizationSuggestions($table, $count, $size)
                ];
            } catch (\Exception $e) {
                $analysis[$table] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $analysis;
    }

    /**
     * Get table size in MB
     *
     * @param string $table
     * @return float
     */
    private function getTableSize(string $table): float
    {
        try {
            $result = DB::select("
                SELECT
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?
            ", [$table]);

            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            // Fallback for SQLite or other databases
            return 0;
        }
    }

    /**
     * Get optimization suggestions for a table
     *
     * @param string $table
     * @param int $count
     * @param float $size
     * @return array
     */
    private function getOptimizationSuggestions(string $table, int $count, float $size): array
    {
        $suggestions = [];

        // Large table suggestions
        if ($count > 10000) {
            $suggestions[] = "Consider partitioning for table with {$count} rows";
        }

        if ($size > 100) {
            $suggestions[] = "Large table ({$size}MB) - consider archiving old data";
        }

        // Table-specific suggestions
        switch ($table) {
            case 'blog_posts':
                if ($count > 1000) {
                    $suggestions[] = "Consider implementing soft deletes for old blog posts";
                    $suggestions[] = "Add full-text search indexes for content fields";
                }
                break;

            case 'contact_messages':
                if ($count > 5000) {
                    $suggestions[] = "Consider archiving old contact messages";
                    $suggestions[] = "Implement message cleanup for read messages older than 1 year";
                }
                break;

            case 'projects':
                $suggestions[] = "Ensure proper indexing on category and featured columns";
                break;

            case 'services':
                $suggestions[] = "Consider caching service list as it's frequently accessed";
                break;
        }

        return $suggestions;
    }

    /**
     * Check index usage and suggest improvements
     *
     * @return array
     */
    public function analyzeIndexUsage(): array
    {
        try {
            // This works for MySQL - would need adaptation for other databases
            $indexStats = DB::select("
                SELECT
                    TABLE_NAME,
                    INDEX_NAME,
                    COLUMN_NAME,
                    CARDINALITY,
                    NULLABLE
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME IN ('admins', 'heroes', 'about', 'services', 'projects', 'blog_posts', 'contact_messages', 'system_settings')
                ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
            ");

            $analysis = [];
            foreach ($indexStats as $stat) {
                $table = $stat->TABLE_NAME;
                if (!isset($analysis[$table])) {
                    $analysis[$table] = [];
                }

                $analysis[$table][] = [
                    'index_name' => $stat->INDEX_NAME,
                    'column_name' => $stat->COLUMN_NAME,
                    'cardinality' => $stat->CARDINALITY,
                    'nullable' => $stat->NULLABLE === 'YES',
                    'effectiveness' => $this->calculateIndexEffectiveness($stat->CARDINALITY)
                ];
            }

            return $analysis;
        } catch (\Exception $e) {
            return ['error' => 'Index analysis not available for this database type'];
        }
    }

    /**
     * Calculate index effectiveness based on cardinality
     *
     * @param int|null $cardinality
     * @return string
     */
    private function calculateIndexEffectiveness(?int $cardinality): string
    {
        if ($cardinality === null || $cardinality === 0) {
            return 'Poor';
        }

        if ($cardinality < 10) {
            return 'Low';
        } elseif ($cardinality < 100) {
            return 'Medium';
        } else {
            return 'High';
        }
    }

    /**
     * Format query time for display
     *
     * @param float $time
     * @return string
     */
    private function formatQueryTime(float $time): string
    {
        if ($time < 1) {
            return number_format($time, 3) . 'ms';
        } elseif ($time < 1000) {
            return number_format($time, 2) . 'ms';
        } else {
            return number_format($time / 1000, 2) . 's';
        }
    }

    /**
     * Optimize database tables
     *
     * @return array
     */
    public function optimizeTables(): array
    {
        $tables = [
            'admins', 'heroes', 'about', 'services', 'projects',
            'blog_posts', 'contact_messages', 'contact_info', 'system_settings'
        ];

        $results = [];

        foreach ($tables as $table) {
            try {
                // For MySQL
                DB::statement("OPTIMIZE TABLE {$table}");
                $results[$table] = 'Optimized successfully';
            } catch (\Exception $e) {
                $results[$table] = 'Optimization failed: ' . $e->getMessage();
            }
        }

        Log::info('Database table optimization completed', [
            'results' => $results,
            'timestamp' => now()->toISOString()
        ]);

        return $results;
    }

    /**
     * Clear query log
     *
     * @return void
     */
    public function clearQueryLog(): void
    {
        DB::flushQueryLog();
    }
}
