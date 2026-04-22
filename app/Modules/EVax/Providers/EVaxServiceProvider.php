<?php

declare(strict_types=1);

namespace App\Modules\EVax\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class EVaxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/evax')
                ->name('evax.api.')
                ->group($routesFile);
        }
    }
}
