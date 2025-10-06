<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    protected $fillable = [
        'category',
        'event',
        'admin_id',
        'data',
        'ip_address',
        'user_agent',
        'session_id',
        'method',
        'url',
        'headers',
        'status_code',
        'response_time',
        'memory_usage',
        'peak_memory',
        'level',
    ];

    protected $casts = [
        'data' => 'array',
        'headers' => 'array',
        'response_time' => 'float',
        'memory_usage' => 'integer',
        'peak_memory' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin that performed the action
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by event
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for filtering by admin
     */
    public function scopeByAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope for filtering by level
     */
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for authentication events
     */
    public function scopeAuthEvents(Builder $query): Builder
    {
        return $query->where('category', 'auth');
    }

    /**
     * Scope for CRUD operations
     */
    public function scopeCrudOperations(Builder $query): Builder
    {
        return $query->where('category', 'crud');
    }

    /**
     * Scope for security events
     */
    public function scopeSecurityEvents(Builder $query): Builder
    {
        return $query->where('category', 'security');
    }

    /**
     * Scope for error events
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('level', 'error');
    }

    /**
     * Scope for slow queries
     */
    public function scopeSlowQueries(Builder $query): Builder
    {
        return $query->where('category', 'performance')
                    ->where('event', 'slow_query');
    }

    /**
     * Get formatted event description
     */
    public function getFormattedEventAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->event));
    }

    /**
     * Get formatted category
     */
    public function getFormattedCategoryAttribute(): string
    {
        return ucwords($this->category);
    }

    /**
     * Check if this is a security-related event
     */
    public function isSecurityEvent(): bool
    {
        return in_array($this->category, ['security', 'auth']) ||
               in_array($this->level, ['warning', 'error']);
    }

    /**
     * Check if this is a high-priority event
     */
    public function isHighPriority(): bool
    {
        return in_array($this->level, ['error', 'critical']) ||
               ($this->category === 'security') ||
               ($this->category === 'auth' && in_array($this->event, ['failed_login', 'account_locked']));
    }
}
