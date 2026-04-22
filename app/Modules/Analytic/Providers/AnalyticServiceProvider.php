<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Providers;

use App\Modules\Analytic\Services\AnalyticService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AnalyticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AnalyticService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/analytic')
                ->name('analytic.api.')
                ->group($routesFile);
        }
    }
}
