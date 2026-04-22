<?php

declare(strict_types=1);

namespace App\Modules\Mwana\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class MwanaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/mwana')
                ->name('mwana.api.')
                ->group($routesFile);
        }
    }
}
