<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Log admin authentication event
     *
     * @param string $event
     * @param array $data
     * @param int|null $adminId
     */
    public function logAuthEvent(string $event, array $data, ?int $adminId = null): void
    {
        $this->log('auth', $event, $data, $adminId, 'info');
    }

    /**
     * Log admin CRUD operation
     *
     * @param string $entity
     * @param string $operation
     * @param array $data
     * @param int|null $adminId
     */
    public function logCrudOperation(string $entity, string $operation, array $data, ?int $adminId = null): void
    {
        $this->log('crud', "{$entity}_{$operation}", $data, $adminId, 'info');
    }

    /**
     * Log file operation
     *
     * @param string $operation
     * @param array $data
     * @param int|null $adminId
     */
    public function logFileOperation(string $operation, array $data, ?int $adminId = null): void
    {
        $this->log('file', $operation, $data, $adminId, 'info');
    }

    /**
     * Log security event
     *
     * @param string $event
     * @param array $data
     * @param int|null $adminId
     */
    public function logSecurityEvent(string $event, array $data, ?int $adminId = null): void
    {
        $this->log('security', $event, $data, $adminId, 'warning');
    }

    /**
     * Log system configuration change
     *
     * @param string $setting
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param int|null $adminId
     */
    public function logConfigChange(string $setting, mixed $oldValue, mixed $newValue, ?int $adminId = null): void
    {
        $this->log('config', 'setting_changed', [
            'setting' => $setting,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ], $adminId, 'info');
    }

    /**
     * Log error event
     *
     * @param string $error
     * @param array $context
     * @param int|null $adminId
     */
    public function logError(string $error, array $context = [], ?int $adminId = null): void
    {
        $this->log('error', $error, $context, $adminId, 'error');
    }

    /**
     * Log API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param int $statusCode
     * @param int|null $adminId
     */
    public function logApiRequest(string $method, string $endpoint, array $data, int $statusCode, ?int $adminId = null): void
    {
        $this->log('api', 'request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'status_code' => $statusCode,
            'response_time' => $this->getResponseTime()
        ], $adminId, $statusCode >= 400 ? 'warning' : 'info');
    }

    /**
     * Log database query performance
     *
     * @param string $query
     * @param float $executionTime
     * @param array $bindings
     */
    public function logSlowQuery(string $query, float $executionTime, array $bindings = []): void
    {
        if ($executionTime > 1000) { // Log queries taking more than 1 second
            $this->log('performance', 'slow_query', [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'bindings' => $bindings
            ], null, 'warning');
        }
    }

    /**
     * Log bulk operation
     *
     * @param string $operation
     * @param string $entity
     * @param int $count
     * @param array $data
     * @param int|null $adminId
     */
    public function logBulkOperation(string $operation, string $entity, int $count, array $data, ?int $adminId = null): void
    {
        $this->log('bulk', "{$entity}_{$operation}", [
            'count' => $count,
            'data' => $data
        ], $adminId, 'info');
    }

    /**
     * Log maintenance event
     *
     * @param string $event
     * @param array $data
     * @param int|null $adminId
     */
    public function logMaintenanceEvent(string $event, array $data, ?int $adminId = null): void
    {
        $this->log('maintenance', $event, $data, $adminId, 'info');
    }

    /**
     * Get audit log statistics
     *
     * @param string $period
     * @return array
     */
    public function getAuditStatistics(string $period = '24h'): array
    {
        $startTime = match($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay()
        };

        try {
            $baseQuery = \App\Models\AuditLog::where('created_at', '>=', $startTime);

            return [
                'period' => $period,
                'total_events' => $baseQuery->count(),
                'auth_events' => (clone $baseQuery)->where('category', 'auth')->count(),
                'crud_operations' => (clone $baseQuery)->where('category', 'crud')->count(),
                'file_operations' => (clone $baseQuery)->where('category', 'file')->count(),
                'security_events' => (clone $baseQuery)->where('category', 'security')->count(),
                'error_events' => (clone $baseQuery)->where('level', 'error')->count(),
                'api_requests' => (clone $baseQuery)->where('category', 'api')->count(),
                'unique_admins' => (clone $baseQuery)->whereNotNull('admin_id')->distinct('admin_id')->count(),
                'start_time' => $startTime->toISOString(),
                'end_time' => now()->toISOString(),
                'categories' => (clone $baseQuery)->groupBy('category')->selectRaw('category, count(*) as count')->pluck('count', 'category')->toArray(),
                'levels' => (clone $baseQuery)->groupBy('level')->selectRaw('level, count(*) as count')->pluck('count', 'level')->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get audit statistics', ['error' => $e->getMessage()]);
            return [
                'period' => $period,
                'total_events' => 0,
                'auth_events' => 0,
                'crud_operations' => 0,
                'file_operations' => 0,
                'security_events' => 0,
                'error_events' => 0,
                'api_requests' => 0,
                'unique_admins' => 0,
                'start_time' => $startTime->toISOString(),
                'end_time' => now()->toISOString(),
                'categories' => [],
                'levels' => [],
            ];
        }
    }

    /**
     * Search audit logs
     *
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function searchAuditLogs(array $filters = [], int $limit = 100): array
    {
        try {
            $query = \App\Models\AuditLog::with('admin:id,username,email');

            // Apply filters
            if (!empty($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (!empty($filters['event'])) {
                $query->where('event', $filters['event']);
            }

            if (!empty($filters['admin_id'])) {
                $query->where('admin_id', $filters['admin_id']);
            }

            if (!empty($filters['level'])) {
                $query->where('level', $filters['level']);
            }

            if (!empty($filters['ip_address'])) {
                $query->where('ip_address', $filters['ip_address']);
            }

            if (!empty($filters['from_date'])) {
                $query->where('created_at', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
                $query->where('created_at', '<=', $filters['to_date']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('event', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('data', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('url', 'like', '%' . $filters['search'] . '%');
                });
            }

            $total = $query->count();
            $logs = $query->orderBy('created_at', 'desc')
                         ->limit($limit)
                         ->get()
                         ->map(function ($log) {
                             return [
                                 'id' => $log->id,
                                 'category' => $log->category,
                                 'event' => $log->event,
                                 'formatted_event' => $log->formatted_event,
                                 'admin' => $log->admin ? [
                                     'id' => $log->admin->id,
                                     'username' => $log->admin->username,
                                     'email' => $log->admin->email,
                                 ] : null,
                                 'data' => $log->data,
                                 'ip_address' => $log->ip_address,
                                 'method' => $log->method,
                                 'url' => $log->url,
                                 'status_code' => $log->status_code,
                                 'response_time' => $log->response_time,
                                 'level' => $log->level,
                                 'is_security_event' => $log->isSecurityEvent(),
                                 'is_high_priority' => $log->isHighPriority(),
                                 'created_at' => $log->created_at->toISOString(),
                             ];
                         });

            return [
                'logs' => $logs,
                'total' => $total,
                'filters' => $filters,
                'limit' => $limit,
                'has_more' => $total > $limit,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search audit logs', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return [
                'logs' => [],
                'total' => 0,
                'filters' => $filters,
                'limit' => $limit,
                'error' => 'Failed to search audit logs',
            ];
        }
    }

    /**
     * Core logging method
     *
     * @param string $category
     * @param string $event
     * @param array $data
     * @param int|null $adminId
     * @param string $level
     */
    private function log(string $category, string $event, array $data, ?int $adminId, string $level): void
    {
        $logData = [
            'category' => $category,
            'event' => $event,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        // Add request context if available
        if (request()) {
            $logData['request'] = [
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'headers' => $this->sanitizeHeaders(request()->headers->all())
            ];
        }

        // Log to Laravel's logging system with appropriate channel
        $channel = $this->getLogChannel($category);
        Log::channel($channel)->log($level, "Admin audit: {$category}.{$event}", $logData);

        // Optionally store in database for better querying
        // This would require creating an audit_logs table
        $this->storeInDatabase($logData);
    }

    /**
     * Store audit log in database
     *
     * @param array $logData
     */
    private function storeInDatabase(array $logData): void
    {
        try {
            \App\Models\AuditLog::create([
                'category' => $logData['category'],
                'event' => $logData['event'],
                'admin_id' => $logData['admin_id'],
                'data' => $logData['data'],
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'session_id' => $logData['session_id'],
                'method' => $logData['request']['method'] ?? null,
                'url' => $logData['request']['url'] ?? null,
                'headers' => $logData['request']['headers'] ?? null,
                'status_code' => $logData['data']['status_code'] ?? null,
                'response_time' => $logData['data']['response_time'] ?? null,
                'memory_usage' => $logData['memory_usage'],
                'peak_memory' => $logData['peak_memory'],
                'level' => $this->mapLogLevel($logData),
            ]);
        } catch (\Exception $e) {
            // Don't fail the main operation if audit logging fails
            Log::error('Failed to store audit log in database', [
                'error' => $e->getMessage(),
                'log_data' => $logData
            ]);
        }
    }

    /**
     * Map log level from context
     *
     * @param array $logData
     * @return string
     */
    private function mapLogLevel(array $logData): string
    {
        // Determine log level based on category and event
        if ($logData['category'] === 'security') {
            return 'warning';
        }

        if ($logData['category'] === 'error') {
            return 'error';
        }

        if ($logData['category'] === 'auth' && str_contains($logData['event'], 'failed')) {
            return 'warning';
        }

        if (isset($logData['data']['status_code']) && $logData['data']['status_code'] >= 400) {
            return $logData['data']['status_code'] >= 500 ? 'error' : 'warning';
        }

        return 'info';
    }

    /**
     * Get appropriate log channel based on category
     *
     * @param string $category
     * @return string
     */
    private function getLogChannel(string $category): string
    {
        return match($category) {
            'auth' => 'auth',
            'security' => 'security',
            'performance' => 'performance',
            'audit', 'crud', 'file', 'bulk', 'config', 'maintenance' => 'audit',
            default => 'single'
        };
    }

    /**
     * Sanitize headers for logging
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Get response time if available
     *
     * @return float|null
     */
    private function getResponseTime(): ?float
    {
        if (defined('LARAVEL_START')) {
            return (microtime(true) - LARAVEL_START) * 1000; // Convert to milliseconds
        }

        return null;
    }

    /**
     * Clean old audit logs
     *
     * @param int $daysToKeep
     * @return int Number of deleted records
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);
            $deletedCount = \App\Models\AuditLog::where('created_at', '<', $cutoffDate)->delete();

            Log::info('Audit log cleanup completed', [
                'days_to_keep' => $daysToKeep,
                'cutoff_date' => $cutoffDate->toISOString(),
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to clean old audit logs', [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep
            ]);
            return 0;
        }
    }
}
