<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        // Repository Interface Bindings
        \App\Repositories\Contracts\HeroRepositoryInterface::class => \App\Repositories\Eloquent\HeroRepository::class,
        \App\Repositories\Contracts\AboutRepositoryInterface::class => \App\Repositories\Eloquent\AboutRepository::class,
        \App\Repositories\Contracts\ServiceRepositoryInterface::class => \App\Repositories\Eloquent\ServiceRepository::class,
        \App\Repositories\Contracts\ProjectRepositoryInterface::class => \App\Repositories\Eloquent\ProjectRepository::class,
        \App\Repositories\Contracts\BlogRepositoryInterface::class => \App\Repositories\Eloquent\BlogRepository::class,
        \App\Repositories\Contracts\SettingsRepositoryInterface::class => \App\Repositories\Eloquent\SettingsRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Custom repository bindings that need special handling
        $this->app->bind(\App\Repositories\Contracts\ContactRepositoryInterface::class, function ($app) {
            return new \App\Repositories\Eloquent\ContactRepository(
                $app->make(\App\Models\ContactMessage::class),
                $app->make(\App\Models\ContactInfo::class)
            );
        });

        // Register Service layer classes with dependency injection
        $this->app->bind(\App\Services\Admin\AuthService::class, function ($app) {
            return new \App\Services\Admin\AuthService();
        });

        // Register CacheService as singleton
        $this->app->singleton(\App\Services\Admin\CacheService::class, function ($app) {
            return new \App\Services\Admin\CacheService();
        });

        $this->app->bind(\App\Services\Admin\HeroService::class, function ($app) {
            return new \App\Services\Admin\HeroService(
                $app->make(\App\Repositories\Contracts\HeroRepositoryInterface::class),
                $app->make(\App\Services\Admin\CacheService::class)
            );
        });

        $this->app->bind(\App\Services\Admin\AboutService::class, function ($app) {
            return new \App\Services\Admin\AboutService(
                $app->make(\App\Repositories\Contracts\AboutRepositoryInterface::class),
                $app->make(\App\Services\Admin\FileUploadService::class),
                $app->make(\App\Services\Admin\CacheService::class)
            );
        });

        $this->app->bind(\App\Services\Admin\ServiceManagementService::class, function ($app) {
            return new \App\Services\Admin\ServiceManagementService(
                $app->make(\App\Repositories\Contracts\ServiceRepositoryInterface::class)
            );
        });

        $this->app->bind(\App\Services\Admin\ProjectService::class, function ($app) {
            return new \App\Services\Admin\ProjectService(
                $app->make(\App\Repositories\Contracts\ProjectRepositoryInterface::class),
                $app->make(\App\Services\Admin\FileUploadService::class)
            );
        });

        $this->app->bind(\App\Services\Admin\BlogService::class, function ($app) {
            return new \App\Services\Admin\BlogService(
                $app->make(\App\Repositories\Contracts\BlogRepositoryInterface::class),
                $app->make(\App\Services\Admin\FileUploadService::class)
            );
        });

        $this->app->bind(\App\Services\Admin\ContactService::class, function ($app) {
            return new \App\Services\Admin\ContactService(
                $app->make(\App\Repositories\Contracts\ContactRepositoryInterface::class)
            );
        });

        $this->app->bind(\App\Services\Admin\SettingsService::class, function ($app) {
            return new \App\Services\Admin\SettingsService(
                $app->make(\App\Repositories\Contracts\SettingsRepositoryInterface::class)
            );
        });

        $this->app->bind(\App\Services\Admin\FileUploadService::class, function ($app) {
            return new \App\Services\Admin\FileUploadService();
        });

        $this->app->bind(\App\Services\Admin\AuditLogService::class, function ($app) {
            return new \App\Services\Admin\AuditLogService();
        });

        $this->app->bind(\App\Services\Admin\DatabaseOptimizationService::class, function ($app) {
            return new \App\Services\Admin\DatabaseOptimizationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
