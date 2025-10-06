<?php

namespace App\Console\Commands;

use App\Services\Admin\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearAdminCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cache:clear
                            {--type=* : Specific cache types to clear (hero, about, services, projects, settings, contact, all)}
                            {--stats : Show cache statistics before clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear admin cache for specific types or all admin cache';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $types = $this->option('type') ?: ['all'];
        $showStats = $this->option('stats');

        if ($showStats) {
            $this->info('Current cache statistics:');
            $stats = $cacheService->getStats();
            $this->table(
                ['Type', 'Total Keys', 'Cached Keys', 'TTL (seconds)', 'Tags'],
                collect($stats)->map(function ($stat, $type) {
                    return [
                        $type,
                        $stat['total_keys'],
                        $stat['cached_keys'],
                        $stat['ttl'],
                        implode(', ', $stat['tags'])
                    ];
                })->toArray()
            );
            $this->newLine();
        }

        $this->info('Clearing admin cache...');
        $clearedCount = 0;

        foreach ($types as $type) {
            try {
                if ($type === 'all') {
                    $cacheService->flushAll();
                    $this->line("✓ All admin cache cleared");
                    $clearedCount++;
                } else {
                    $cacheService->flushType($type);
                    $this->line("✓ {$type} cache cleared");
                    $clearedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to clear {$type} cache: " . $e->getMessage());
                Log::error("Cache clearing failed for {$type}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info("Cache clearing completed!");
        $this->line("Cleared: {$clearedCount} cache types");

        // Log the clearing operation
        Log::info('Admin cache clearing completed', [
            'types' => $types,
            'cleared_count' => $clearedCount,
            'timestamp' => now()->toISOString()
        ]);

        return Command::SUCCESS;
    }
}
