<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AnnuaireServiceProvider.
 *
 * Registers the Annuaire module:
 *   - Health structures directory (hostos)
 *   - Public search, geolocation, detailed profiles
 *
 * All routes are public (no authentication required in Phase 1).
 */
final class AnnuaireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';

        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/annuaire')
                ->name('annuaire.api.')
                ->group($routesFile);
        }
    }
}
