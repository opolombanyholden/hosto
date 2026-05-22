<?php

declare(strict_types=1);

namespace App\Modules\EVax\Providers;

use App\Modules\EVax\Console\Commands\GenerateCarnetKeypair;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class EVaxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $apiRoutes = __DIR__.'/../Routes/api.php';
        if (file_exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/evax')
                ->name('evax.api.')
                ->group($apiRoutes);
        }

        $webRoutes = __DIR__.'/../Routes/web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }

        $this->loadViewsFrom(__DIR__.'/../../../../resources/views/evax', 'evax');

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateCarnetKeypair::class]);
        }
    }
}
