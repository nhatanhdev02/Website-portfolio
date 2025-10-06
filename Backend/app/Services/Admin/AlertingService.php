<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Exception;

class AlertingService
{
    private const ALERT_CACHE_PREFIX = 'alert_throttle_';
    private const ALERT_HISTORY_PREFIX = 'alert_history_';

    /**
     * Send alert based on type and severity.
     */
    public function sendAlert(string $type, string $message, array $data = [], string $severity = 'warning'): void
    {
        if (!config('monitoring.notifications.enabled', true)) {
            return;
        }

        // Check if alert should be throttled
        if ($this->shouldThrottleAlert($type, $severity)) {
            Log::info('Alert throttled', [
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
            ]);
            return;
        }

        $alert = [
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'server' => gethostname(),
        ];

        // Log the alert
        Log::channel('daily')->{$severity}('System Alert', $alert);

        // Send notifications based on configuration
        $this->sendNotifications($alert);

        // Record alert in history
        $this->recordAlertHistory($alert);

        // Update throttle cache
        $this->updateAlertThrottle($type, $severity);
    }

    /**
     * Send health check failure alert.
     */
    public function sendHealthCheckAlert(string $component, array $checkResult): void
    {
        $severity = $checkResult['status'] === 'unhealthy' ? 'critical' : 'warning';

        $this->sendAlert(
            "health_check_{$component}",
            "Health check failed for {$component}: {$checkResult['message']}",
            $checkResult,
            $severity
        );
    }

    /**
     * Send performance alert.
     */
    public function sendPerformanceAlert(string $metric, float $value, float $threshold, string $unit = ''): void
    {
        $this->sendAlert(
            "performance_{$metric}",
            "Performance threshold exceeded for {$metric}: {$value}{$unit} (threshold: {$threshold}{$unit})",
            [
                'metric' => $metric,
                'value' => $value,
                'threshold' => $threshold,
                'unit' => $unit,
            ],
            'warning'
        );
    }

    /**
     * Send error rate alert.
     */
    public function sendErrorRateAlert(int $errorCount, int $timeWindow): void
    {
        $this->sendAlert(
            'error_rate',
            "High error rate detected: {$errorCount} errors in {$timeWindow} minutes",
            [
                'error_count' => $errorCount,
                'time_window' => $timeWindow,
                'rate_per_minute' => round($errorCount / $timeWindow, 2),
            ],
            'critical'
        );
    }

    /**
     * Send disk space alert.
     */
    public function sendDiskSpaceAlert(float $usagePercentage, string $path): void
    {
        $severity = $usagePercentage > 95 ? 'critical' : 'warning';

        $this->sendAlert(
            'disk_space',
            "Disk space usage high: {$usagePercentage}% on {$path}",
            [
                'usage_percentage' => $usagePercentage,
                'path' => $path,
            ],
            $severity
        );
    }

    /**
     * Send database connection alert.
     */
    public function sendDatabaseAlert(string $message, array $metrics = []): void
    {
        $this->sendAlert(
            'database_connection',
            "Database issue detected: {$message}",
            $metrics,
            'critical'
        );
    }

    /**
     * Send cache system alert.
     */
    public function sendCacheAlert(string $message, array $metrics = []): void
    {
        $this->sendAlert(
            'cache_system',
            "Cache system issue: {$message}",
            $metrics,
            'warning'
        );
    }

    /**
     * Check if alert should be throttled.
     */
    private function shouldThrottleAlert(string $type, string $severity): bool
    {
        $throttleConfig = config('monitoring.notifications.throttle', []);
        $sameAlertMinutes = $throttleConfig['same_alert_minutes'] ?? 30;
        $maxAlertsPerHour = $throttleConfig['max_alerts_per_hour'] ?? 10;

        // Check if same alert was sent recently
        $sameAlertKey = self::ALERT_CACHE_PREFIX . "same_{$type}_{$severity}";
        if (Cache::has($sameAlertKey)) {
            return true;
        }

        // Check hourly alert limit
        $hourlyKey = self::ALERT_CACHE_PREFIX . 'hourly_' . now()->format('Y-m-d-H');
        $hourlyCount = Cache::get($hourlyKey, 0);

        if ($hourlyCount >= $maxAlertsPerHour) {
            return true;
        }

        return false;
    }

    /**
     * Update alert throttle cache.
     */
    private function updateAlertThrottle(string $type, string $severity): void
    {
        $throttleConfig = config('monitoring.notifications.throttle', []);
        $sameAlertMinutes = $throttleConfig['same_alert_minutes'] ?? 30;

        // Set same alert throttle
        $sameAlertKey = self::ALERT_CACHE_PREFIX . "same_{$type}_{$severity}";
        Cache::put($sameAlertKey, true, $sameAlertMinutes * 60);

        // Increment hourly counter
        $hourlyKey = self::ALERT_CACHE_PREFIX . 'hourly_' . now()->format('Y-m-d-H');
        $currentCount = Cache::get($hourlyKey, 0);
        Cache::put($hourlyKey, $currentCount + 1, 3600);
    }

    /**
     * Send notifications via configured channels.
     */
    private function sendNotifications(array $alert): void
    {
        $channels = config('monitoring.notifications.channels', []);

        // Send email notification
        if ($channels['email'] ?? false) {
            $this->sendEmailNotification($alert);
        }

        // Send Slack notification
        if ($channels['slack'] ?? false) {
            $this->sendSlackNotification($alert);
        }

        // Send Discord notification
        if ($channels['discord'] ?? false) {
            $this->sendDiscordNotification($alert);
        }
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(array $alert): void
    {
        try {
            $recipients = explode(',', config('monitoring.notifications.recipients.email', ''));
            $recipients = array_filter(array_map('trim', $recipients));

            if (empty($recipients)) {
                return;
            }

            $subject = "[{$alert['environment']}] {$alert['severity']} Alert: {$alert['type']}";

            foreach ($recipients as $recipient) {
                Mail::raw($this->formatAlertForEmail($alert), function ($message) use ($recipient, $subject) {
                    $message->to($recipient)
                           ->subject($subject);
                });
            }

        } catch (Exception $e) {
            Log::error('Failed to send email alert', [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Send Slack notification.
     */
    private function sendSlackNotification(array $alert): void
    {
        try {
            $webhookUrl = config('monitoring.notifications.recipients.slack_webhook');

            if (!$webhookUrl) {
                return;
            }

            $color = match ($alert['severity']) {
                'critical' => 'danger',
                'warning' => 'warning',
                default => 'good',
            };

            $payload = [
                'text' => "System Alert: {$alert['type']}",
                'attachments' => [
                    [
                        'color' => $color,
                        'fields' => [
                            [
                                'title' => 'Severity',
                                'value' => strtoupper($alert['severity']),
                                'short' => true,
                            ],
                            [
                                'title' => 'Environment',
                                'value' => $alert['environment'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Message',
                                'value' => $alert['message'],
                                'short' => false,
                            ],
                            [
                                'title' => 'Timestamp',
                                'value' => $alert['timestamp'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Server',
                                'value' => $alert['server'],
                                'short' => true,
                            ],
                        ],
                    ],
                ],
            ];

            Http::post($webhookUrl, $payload);

        } catch (Exception $e) {
            Log::error('Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Send Discord notification.
     */
    private function sendDiscordNotification(array $alert): void
    {
        try {
            $webhookUrl = config('monitoring.notifications.recipients.discord_webhook');

            if (!$webhookUrl) {
                return;
            }

            $color = match ($alert['severity']) {
                'critical' => 15158332, // Red
                'warning' => 16776960,  // Yellow
                default => 65280,       // Green
            };

            $payload = [
                'embeds' => [
                    [
                        'title' => "System Alert: {$alert['type']}",
                        'description' => $alert['message'],
                        'color' => $color,
                        'fields' => [
                            [
                                'name' => 'Severity',
                                'value' => strtoupper($alert['severity']),
                                'inline' => true,
                            ],
                            [
                                'name' => 'Environment',
                                'value' => $alert['environment'],
                                'inline' => true,
                            ],
                            [
                                'name' => 'Server',
                                'value' => $alert['server'],
                                'inline' => true,
                            ],
                        ],
                        'timestamp' => $alert['timestamp'],
                    ],
                ],
            ];

            Http::post($webhookUrl, $payload);

        } catch (Exception $e) {
            Log::error('Failed to send Discord alert', [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Format alert for email.
     */
    private function formatAlertForEmail(array $alert): string
    {
        $content = "System Alert Notification\n";
        $content .= "========================\n\n";
        $content .= "Type: {$alert['type']}\n";
        $content .= "Severity: " . strtoupper($alert['severity']) . "\n";
        $content .= "Environment: {$alert['environment']}\n";
        $content .= "Server: {$alert['server']}\n";
        $content .= "Timestamp: {$alert['timestamp']}\n\n";
        $content .= "Message:\n{$alert['message']}\n\n";

        if (!empty($alert['data'])) {
            $content .= "Additional Data:\n";
            foreach ($alert['data'] as $key => $value) {
                $content .= "  {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }

        return $content;
    }

    /**
     * Record alert in history.
     */
    private function recordAlertHistory(array $alert): void
    {
        try {
            $historyKey = self::ALERT_HISTORY_PREFIX . now()->format('Y-m-d');
            $history = Cache::get($historyKey, []);

            $history[] = $alert;

            // Keep only last 100 alerts per day
            if (count($history) > 100) {
                $history = array_slice($history, -100);
            }

            Cache::put($historyKey, $history, 86400); // 24 hours

        } catch (Exception $e) {
            Log::error('Failed to record alert history', [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Get alert history for dashboard.
     */
    public function getAlertHistory(int $days = 7): array
    {
        $history = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $historyKey = self::ALERT_HISTORY_PREFIX . $date->format('Y-m-d');
            $dayHistory = Cache::get($historyKey, []);

            if (!empty($dayHistory)) {
                $history[$date->format('Y-m-d')] = $dayHistory;
            }
        }

        return $history;
    }

    /**
     * Clear old alert history.
     */
    public function clearOldAlertHistory(int $daysToKeep = 30): void
    {
        for ($i = $daysToKeep; $i < 60; $i++) {
            $date = now()->subDays($i);
            $historyKey = self::ALERT_HISTORY_PREFIX . $date->format('Y-m-d');
            Cache::forget($historyKey);
        }
    }
}
