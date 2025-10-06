<?php

namespace App\Console\Commands;

use App\Services\Admin\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AdminCacheStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cache:stats
                            {--detailed : Show detailed cache information}
                            {--redis : Show Redis-specific statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display admin cache statistics and information';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $this->info('Admin Cache Statistics');
        $this->line('========================');

        // Basic cache statistics
        $stats = $cacheService->getStats();

        $this->table(
            ['Type', 'Total Keys', 'Cached Keys', 'Hit Rate', 'TTL (seconds)', 'Tags'],
            collect($stats)->map(function ($stat, $type) {
                $hitRate = $stat['total_keys'] > 0
                    ? round(($stat['cached_keys'] / $stat['total_keys']) * 100, 1) . '%'
                    : '0%';

                return [
                    $type,
                    $stat['total_keys'],
                    $stat['cached_keys'],
                    $hitRate,
                    $stat['ttl'],
                    implode(', ', $stat['tags'])
                ];
            })->toArray()
        );

        // Cache configuration
        $this->newLine();
        $this->info('Cache Configuration:');
        $this->line('Default Store: ' . config('cache.default'));
        $this->line('Cache Prefix: ' . config('cache.prefix'));
        $this->line('Cache Warming: ' . (config('admin_cache.warming.enabled') ? 'Enabled' : 'Disabled'));

        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('Detailed Cache Keys:');

            $keys = config('admin_cache.keys');
            foreach ($keys as $keyName => $cacheKey) {
                $type = explode('_', $keyName)[0];
                $exists = $cacheService->has($keyName, $type);
                $status = $exists ? '✓ Cached' : '✗ Not Cached';
                $this->line("{$keyName}: {$cacheKey} - {$status}");
            }
        }

        if ($this->option('redis')) {
            $this->newLine();
            $this->info('Redis Statistics:');

            try {
                $redis = Redis::connection('cache');
                $info = $redis->info();

                $this->line('Redis Version: ' . ($info['redis_version'] ?? 'Unknown'));
                $this->line('Used Memory: ' . ($info['used_memory_human'] ?? 'Unknown'));
                $this->line('Connected Clients: ' . ($info['connected_clients'] ?? 'Unknown'));
                $this->line('Total Commands Processed: ' . ($info['total_commands_processed'] ?? 'Unknown'));
                $this->line('Keyspace Hits: ' . ($info['keyspace_hits'] ?? 'Unknown'));
                $this->line('Keyspace Misses: ' . ($info['keyspace_misses'] ?? 'Unknown'));

                if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                    $totalRequests = $info['keyspace_hits'] + $info['keyspace_misses'];
                    $hitRatio = $totalRequests > 0
                        ? round(($info['keyspace_hits'] / $totalRequests) * 100, 2)
                        : 0;
                    $this->line('Hit Ratio: ' . $hitRatio . '%');
                }

            } catch (\Exception $e) {
                $this->error('Failed to get Redis statistics: ' . $e->getMessage());
            }
        }

        // Cache warming schedule
        $this->newLine();
        $this->info('Cache Warming Configuration:');
        $this->line('Schedule: ' . config('admin_cache.warming.schedule'));
        $this->line('Warming Keys: ' . implode(', ', config('admin_cache.warming.keys', [])));

        return Command::SUCCESS;
    }
}
