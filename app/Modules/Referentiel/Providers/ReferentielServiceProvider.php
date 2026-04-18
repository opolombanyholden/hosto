<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * ReferentielServiceProvider.
 *
 * Registers the Referentiel module:
 *   - Geographical reference data (countries, regions, cities)
 *   - Health reference data (specialties, structure types, services) — Phase 1.2
 *   - Medication catalog — Phase 2
 *
 * Routes are public (no authentication required): browsing referentials
 * is a valid use case for unauthenticated usage (Phase 1 goal).
 *
 * @see docs/adr/0008-referentiels-geographiques.md
 */
final class ReferentielServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Route::middleware('api')
            ->prefix('api/'.config('hosto.api.current_version').'/referentiel')
            ->name('referentiel.api.')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
