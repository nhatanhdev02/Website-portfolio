<?php

namespace App\Providers;

use App\Models\Service;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure route model binding for admin routes
        $this->configureRouteModelBinding();

        // Set up database query logging for performance monitoring
        $this->configureQueryLogging();
    }

    /**
     * Configure route model binding for automatic model injection
     */
    private function configureRouteModelBinding(): void
    {
        // Bind route parameters to models for automatic injection
        Route::model('service', Service::class);
        Route::model('project', Project::class);
        Route::model('blogPost', BlogPost::class);
        Route::model('contactMessage', ContactMessage::class);
    }

    /**
     * Configure database query logging for performance monitoring
     */
    private function configureQueryLogging(): void
    {
        if (app()->environment(['local', 'staging']) || config('app.debug')) {
            \Illuminate\Support\Facades\DB::listen(function ($query) {
                $auditService = app(\App\Services\Admin\AuditLogService::class);
                $auditService->logSlowQuery(
                    $query->sql,
                    $query->time,
                    $query->bindings
                );
            });
        }
    }
}
