<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Get cache TTL for a specific content type
     *
     * @param string $type
     * @return int
     */
    public function getTtl(string $type): int
    {
        return config("admin_cache.ttl.{$type}", config('admin_cache.ttl.default', 3600));
    }

    /**
     * Get cache key for a specific content type
     *
     * @param string $key
     * @param array $params
     * @return string
     */
    public function getKey(string $key, array $params = []): string
    {
        $baseKey = config("admin_cache.keys.{$key}", "admin:{$key}");

        if (!empty($params)) {
            $paramString = implode(':', array_map(function ($value) {
                return is_array($value) ? md5(serialize($value)) : $value;
            }, $params));
            $baseKey .= ":{$paramString}";
        }

        return $baseKey;
    }

    /**
     * Get cache tags for a specific content type
     *
     * @param string $type
     * @return array
     */
    public function getTags(string $type): array
    {
        return config("admin_cache.tags.{$type}", ['admin']);
    }

    /**
     * Remember cache with automatic TTL and tags
     *
     * @param string $key
     * @param string $type
     * @param callable $callback
     * @param array $params
     * @return mixed
     */
    public function remember(string $key, string $type, callable $callback, array $params = []): mixed
    {
        $cacheKey = $this->getKey($key, $params);
        $ttl = $this->getTtl($type);
        $tags = $this->getTags($type);

        $this->logCacheOperation('remember', $cacheKey, $type, $ttl);

        return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Put data in cache with automatic TTL and tags
     *
     * @param string $key
     * @param string $type
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function put(string $key, string $type, mixed $value, array $params = []): bool
    {
        $cacheKey = $this->getKey($key, $params);
        $ttl = $this->getTtl($type);
        $tags = $this->getTags($type);

        $this->logCacheOperation('put', $cacheKey, $type, $ttl);

        return Cache::tags($tags)->put($cacheKey, $value, $ttl);
    }

    /**
     * Get data from cache
     *
     * @param string $key
     * @param string $type
     * @param mixed $default
     * @param array $params
     * @return mixed
     */
    public function get(string $key, string $type, mixed $default = null, array $params = []): mixed
    {
        $cacheKey = $this->getKey($key, $params);
        $tags = $this->getTags($type);

        $this->logCacheOperation('get', $cacheKey, $type);

        return Cache::tags($tags)->get($cacheKey, $default);
    }

    /**
     * Forget specific cache key
     *
     * @param string $key
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function forget(string $key, string $type, array $params = []): bool
    {
        $cacheKey = $this->getKey($key, $params);
        $tags = $this->getTags($type);

        $this->logCacheOperation('forget', $cacheKey, $type);

        return Cache::tags($tags)->forget($cacheKey);
    }

    /**
     * Flush all cache for a specific type
     *
     * @param string $type
     * @return bool
     */
    public function flushType(string $type): bool
    {
        $tags = $this->getTags($type);

        $this->logCacheOperation('flush_type', implode(',', $tags), $type);

        return Cache::tags($tags)->flush();
    }

    /**
     * Flush all admin cache
     *
     * @return bool
     */
    public function flushAll(): bool
    {
        $this->logCacheOperation('flush_all', 'admin', 'all');

        return Cache::tags(['admin'])->flush();
    }

    /**
     * Check if cache key exists
     *
     * @param string $key
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function has(string $key, string $type, array $params = []): bool
    {
        $cacheKey = $this->getKey($key, $params);
        $tags = $this->getTags($type);

        return Cache::tags($tags)->has($cacheKey);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = [];
        $types = ['hero', 'about', 'services', 'projects', 'blog', 'settings', 'contact'];

        foreach ($types as $type) {
            $keys = config("admin_cache.keys");
            $typeKeys = array_filter($keys, function ($key) use ($type) {
                return str_contains($key, $type);
            }, ARRAY_FILTER_USE_KEY);

            $stats[$type] = [
                'total_keys' => count($typeKeys),
                'cached_keys' => 0,
                'ttl' => $this->getTtl($type),
                'tags' => $this->getTags($type)
            ];

            foreach ($typeKeys as $keyName => $cacheKey) {
                if ($this->has($keyName, $type)) {
                    $stats[$type]['cached_keys']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Warm up cache for frequently accessed data
     *
     * @return array
     */
    public function warmUp(): array
    {
        if (!config('admin_cache.warming.enabled', true)) {
            return ['status' => 'disabled'];
        }

        $warmedKeys = [];
        $warmingKeys = config('admin_cache.warming.keys', []);

        foreach ($warmingKeys as $keyName) {
            try {
                // This would need to be implemented based on specific service methods
                $warmedKeys[] = $keyName;
                $this->logCacheOperation('warm_up', $keyName, 'warming');
            } catch (\Exception $e) {
                Log::error("Cache warming failed for key: {$keyName}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return [
            'status' => 'completed',
            'warmed_keys' => $warmedKeys,
            'total_keys' => count($warmingKeys)
        ];
    }

    /**
     * Log cache operations for monitoring
     *
     * @param string $operation
     * @param string $key
     * @param string $type
     * @param int|null $ttl
     */
    private function logCacheOperation(string $operation, string $key, string $type, ?int $ttl = null): void
    {
        if (config('app.debug', false)) {
            Log::debug('Cache operation', [
                'operation' => $operation,
                'key' => $key,
                'type' => $type,
                'ttl' => $ttl,
                'timestamp' => now()->toISOString()
            ]);
        }
    }
}
