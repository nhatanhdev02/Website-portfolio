<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\HeroController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All admin routes are prefixed with /api/admin and protected with
| authentication middleware. Route model binding is configured for
| automatic model injection in AppServiceProvider.
|
| Route Structure:
| - Authentication: /api/admin/auth/*
| - Content Management: /api/admin/{resource}/*
| - Custom Operations: reorder, bulk-action, toggle-featured
| - Special Endpoints: published/list, drafts/list, unread-count
|
*/

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/auth')->name('admin.auth.')->middleware(['ip.whitelist'])->group(function () {
    // Public authentication routes with strict rate limiting
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware('throttle:admin-auth');

    // Protected authentication routes
    Route::middleware(['auth:sanctum', 'admin.auth', 'throttle:admin-api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
|
| All admin content management routes with authentication middleware,
| rate limiting, and request logging. Route model binding automatically
| injects models based on route parameters.
|
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['ip.whitelist', 'auth:sanctum', 'admin.auth', 'request.logging', 'throttle:admin-api'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Hero Section Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('hero')->name('hero.')->group(function () {
            Route::get('/', [HeroController::class, 'show'])->name('show');
            Route::put('/', [HeroController::class, 'update'])->name('update');
        });

        /*
        |--------------------------------------------------------------------------
        | About Section Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('about')->name('about.')->group(function () {
            Route::get('/', [AboutController::class, 'show'])->name('show');
            Route::put('/', [AboutController::class, 'update'])->name('update');
            Route::post('/image', [AboutController::class, 'uploadImage'])
                ->name('upload-image')
                ->middleware('throttle:file-upload'); // Limit image uploads
        });

        /*
        |--------------------------------------------------------------------------
        | Services Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('services')->name('services.')->group(function () {
            // RESTful routes with route model binding
            Route::get('/', [ServiceController::class, 'index'])->name('index');
            Route::post('/', [ServiceController::class, 'store'])->name('store');
            Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
            Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
            Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');

            // Custom operations
            Route::put('/reorder', [ServiceController::class, 'reorder'])->name('reorder');

            // Bulk operations
            Route::post('/bulk-action', [ServiceController::class, 'bulkAction'])
                ->name('bulk-action')
                ->middleware('throttle:bulk-operations');
        });

        /*
        |--------------------------------------------------------------------------
        | Projects Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('projects')->name('projects.')->group(function () {
            // RESTful routes with route model binding
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::post('/', [ProjectController::class, 'store'])
                ->name('store')
                ->middleware('throttle:file-upload'); // Limit project creation with file uploads
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');

            // Custom operations
            Route::put('/{project}/toggle-featured', [ProjectController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::get('/featured/list', [ProjectController::class, 'featured'])->name('featured');
            Route::put('/reorder', [ProjectController::class, 'reorder'])->name('reorder');

            // Bulk operations
            Route::post('/bulk-action', [ProjectController::class, 'bulkAction'])
                ->name('bulk-action')
                ->middleware('throttle:bulk-operations');
        });

        /*
        |--------------------------------------------------------------------------
        | Blog Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('blog')->name('blog.')->group(function () {
            // RESTful routes with route model binding
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::post('/', [BlogController::class, 'store'])->name('store');
            Route::get('/{blogPost}', [BlogController::class, 'show'])->name('show');
            Route::put('/{blogPost}', [BlogController::class, 'update'])->name('update');
            Route::delete('/{blogPost}', [BlogController::class, 'destroy'])->name('destroy');

            // Publishing workflow
            Route::put('/{blogPost}/publish', [BlogController::class, 'publish'])->name('publish');
            Route::put('/{blogPost}/unpublish', [BlogController::class, 'unpublish'])->name('unpublish');

            // Content filtering
            Route::get('/published/list', [BlogController::class, 'published'])->name('published');
            Route::get('/drafts/list', [BlogController::class, 'drafts'])->name('drafts');

            // Bulk operations
            Route::post('/bulk-action', [BlogController::class, 'bulkAction'])
                ->name('bulk-action')
                ->middleware('throttle:bulk-operations');
        });

        /*
        |--------------------------------------------------------------------------
        | Contact Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('contacts')->name('contacts.')->group(function () {
            // Message management with route model binding
            Route::prefix('messages')->name('messages.')->group(function () {
                Route::get('/', [ContactController::class, 'messages'])->name('index');
                Route::get('/{contactMessage}', [ContactController::class, 'showMessage'])->name('show');
                Route::put('/{contactMessage}/read', [ContactController::class, 'markAsRead'])->name('mark-read');
                Route::put('/{contactMessage}/unread', [ContactController::class, 'markAsUnread'])->name('mark-unread');
                Route::delete('/{contactMessage}', [ContactController::class, 'deleteMessage'])->name('delete');

                // Bulk operations
                Route::post('/bulk-action', [ContactController::class, 'bulkAction'])
                    ->name('bulk-action')
                    ->middleware('throttle:bulk-operations');
                Route::get('/unread-count', [ContactController::class, 'unreadCount'])->name('unread-count');
            });

            // Contact info management
            Route::prefix('info')->name('info.')->group(function () {
                Route::get('/', [ContactController::class, 'info'])->name('show');
                Route::put('/', [ContactController::class, 'updateInfo'])->name('update');
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Settings Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')->name('settings.')->group(function () {
            // General settings
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
            Route::get('/{key}', [SettingsController::class, 'show'])->name('show');
            Route::put('/{key}', [SettingsController::class, 'updateSetting'])->name('update-setting');

            // Language configuration
            Route::prefix('language')->name('language.')->group(function () {
                Route::get('/config', [SettingsController::class, 'language'])->name('config');
                Route::put('/config', [SettingsController::class, 'updateLanguage'])->name('update');
            });

            // Theme configuration
            Route::prefix('theme')->name('theme.')->group(function () {
                Route::get('/config', [SettingsController::class, 'theme'])->name('config');
                Route::put('/config', [SettingsController::class, 'updateTheme'])->name('update');
            });

            // System operations
            Route::post('/maintenance/toggle', [SettingsController::class, 'toggleMaintenance'])
                ->name('maintenance.toggle')
                ->middleware('throttle:system-operations'); // Limit maintenance mode toggles
            Route::post('/reset', [SettingsController::class, 'reset'])
                ->name('reset')
                ->middleware('throttle:system-operations'); // Strict limit on reset operations
        });
    });

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Admin\MonitoringController;

Route::prefix('health')->name('health.')->group(function () {
    // Public health check endpoints
    Route::get('/ping', [HealthCheckController::class, 'ping'])->name('ping');

    // Comprehensive health check (requires secret in production)
    Route::get('/', [HealthCheckController::class, 'index'])->name('index');

    // Component-specific health checks
    Route::get('/database', [HealthCheckController::class, 'database'])->name('database');
    Route::get('/cache', [HealthCheckController::class, 'cache'])->name('cache');
});

/*
|--------------------------------------------------------------------------
| Admin Monitoring Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin/monitoring')
    ->name('admin.monitoring.')
    ->middleware(['ip.whitelist', 'auth:sanctum', 'admin.auth', 'throttle:admin-api'])
    ->group(function () {
        // Monitoring dashboard
        Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('dashboard');

        // System metrics
        Route::get('/metrics', [MonitoringController::class, 'metrics'])->name('metrics');

        // Alert history
        Route::get('/alerts', [MonitoringController::class, 'alerts'])->name('alerts');

        // Performance analytics
        Route::get('/performance', [MonitoringController::class, 'performance'])->name('performance');

        // Test alert system
        Route::post('/test-alert', [MonitoringController::class, 'testAlert'])
            ->name('test-alert')
            ->middleware('throttle:system-operations');
    });

/*
|--------------------------------------------------------------------------
| General API Routes
|--------------------------------------------------------------------------
*/
Route::get('/user', fn(Request $request) => $request->user())
    ->middleware('auth:sanctum')
    ->name('user');
