<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\AlertingService;

class TestAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:alert:test
                            {type : Alert type (info, warning, critical)}
                            {--message= : Custom alert message}
                            {--channel= : Specific notification channel (email, slack, discord)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the alerting system by sending a test alert';

    private AlertingService $alertingService;

    public function __construct(AlertingService $alertingService)
    {
        parent::__construct();
        $this->alertingService = $alertingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $message = $this->option('message') ?? "Test alert of type: {$type}";
        $channel = $this->option('channel');

        // Validate alert type
        if (!in_array($type, ['info', 'warning', 'critical'])) {
            $this->error('Invalid alert type. Must be one of: info, warning, critical');
            return 1;
        }

        try {
            $this->info("🧪 Sending test alert...");
            $this->info("Type: {$type}");
            $this->info("Message: {$message}");

            if ($channel) {
                $this->info("Channel: {$channel}");
            }

            // Temporarily override channel configuration if specified
            $originalChannels = config('monitoring.notifications.channels');
            if ($channel) {
                config([
                    'monitoring.notifications.channels.email' => $channel === 'email',
                    'monitoring.notifications.channels.slack' => $channel === 'slack',
                    'monitoring.notifications.channels.discord' => $channel === 'discord',
                ]);
            }

            // Send the test alert
            $this->alertingService->sendAlert(
                'test_alert_command',
                $message,
                [
                    'test_data' => 'This is test data',
                    'command_executed_by' => 'CLI',
                    'timestamp' => now()->toISOString(),
                    'environment' => app()->environment(),
                ],
                $type
            );

            // Restore original configuration
            if ($channel) {
                config(['monitoring.notifications.channels' => $originalChannels]);
            }

            $this->info("✅ Test alert sent successfully!");
            $this->newLine();
            $this->info("Check your configured notification channels to verify delivery.");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send test alert: {$e->getMessage()}");
            return 1;
        }
    }
}
