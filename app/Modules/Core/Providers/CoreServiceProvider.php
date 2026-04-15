<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * CoreServiceProvider.
 *
 * Registers the Core module:
 *   - Migrations (audit_logs, users, access tokens, ...)
 *   - Routes (/api/v1/core/*)
 *   - Policies
 *   - Singletons (AuditLogger)
 *
 * Core is always loaded and cannot be deactivated via config.
 *
 * @see docs/adr/0001-architecture-monolithique-modulaire.md
 */
final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $routesFile = __DIR__.'/../Routes/api.php';

        if (! file_exists($routesFile)) {
            return;
        }

        Route::middleware('api')
            ->prefix('api/'.config('hosto.api.current_version').'/core')
            ->name('core.api.')
            ->group($routesFile);
    }
}
