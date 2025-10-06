<?php

namespace App\Console\Commands;

use App\Services\Admin\CacheService;
use App\Services\Admin\HeroService;
use App\Services\Admin\AboutService;
use App\Services\Admin\ServiceManagementService;
use App\Services\Admin\ProjectService;
use App\Services\Admin\SettingsService;
use App\Services\Admin\ContactService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmAdminCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cache:warm
                            {--type=* : Specific cache types to warm (hero, about, services, projects, settings, contact)}
                            {--force : Force cache refresh even if already cached}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up admin cache for frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(
        CacheService $cacheService,
        HeroService $heroService,
        AboutService $aboutService,
        ServiceManagementService $serviceService,
        ProjectService $projectService,
        SettingsService $settingsService,
        ContactService $contactService
    ): int {
        $this->info('Starting admin cache warming...');

        $types = $this->option('type') ?: ['hero', 'about', 'services', 'projects', 'settings', 'contact'];
        $force = $this->option('force');
        $warmedCount = 0;
        $skippedCount = 0;

        foreach ($types as $type) {
            try {
                $this->info("Warming {$type} cache...");

                switch ($type) {
                    case 'hero':
                        if ($force || !$cacheService->has('hero_content', 'hero')) {
                            $heroService->getHeroContent();
                            $warmedCount++;
                            $this->line("✓ Hero content cached");
                        } else {
                            $skippedCount++;
                            $this->line("- Hero content already cached (use --force to refresh)");
                        }
                        break;

                    case 'about':
                        if ($force || !$cacheService->has('about_content', 'about')) {
                            $aboutService->getAboutContent();
                            $warmedCount++;
                            $this->line("✓ About content cached");
                        } else {
                            $skippedCount++;
                            $this->line("- About content already cached (use --force to refresh)");
                        }
                        break;

                    case 'services':
                        if ($force || !$cacheService->has('services_ordered', 'services')) {
                            // This would need to be implemented in ServiceManagementService
                            $this->line("- Services caching not yet implemented");
                        }
                        break;

                    case 'projects':
                        if ($force || !$cacheService->has('projects_featured', 'projects')) {
                            // This would need to be implemented in ProjectService
                            $this->line("- Projects caching not yet implemented");
                        }
                        break;

                    case 'settings':
                        if ($force || !$cacheService->has('settings_all', 'settings')) {
                            // This would need to be implemented in SettingsService
                            $this->line("- Settings caching not yet implemented");
                        }
                        break;

                    case 'contact':
                        if ($force || !$cacheService->has('contact_info', 'contact')) {
                            // This would need to be implemented in ContactService
                            $this->line("- Contact caching not yet implemented");
                        }
                        break;

                    default:
                        $this->error("Unknown cache type: {$type}");
                        continue 2;
                }
            } catch (\Exception $e) {
                $this->error("Failed to warm {$type} cache: " . $e->getMessage());
                Log::error("Cache warming failed for {$type}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info("Cache warming completed!");
        $this->line("Warmed: {$warmedCount} cache entries");
        $this->line("Skipped: {$skippedCount} cache entries");

        // Log the warming operation
        Log::info('Admin cache warming completed', [
            'types' => $types,
            'warmed_count' => $warmedCount,
            'skipped_count' => $skippedCount,
            'force' => $force,
            'timestamp' => now()->toISOString()
        ]);

        return Command::SUCCESS;
    }
}
