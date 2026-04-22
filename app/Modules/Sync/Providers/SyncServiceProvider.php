<?php

declare(strict_types=1);

namespace App\Modules\Sync\Providers;

use App\Modules\Sync\Services\SyncService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SyncService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/sync')
                ->name('sync.api.')
                ->group($routesFile);
        }
    }
}
