<?php

declare(strict_types=1);

use App\Modules\Referentiel\Http\Controllers\CitiesController;
use App\Modules\Referentiel\Http\Controllers\CountriesController;
use App\Modules\Referentiel\Http\Controllers\RegionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Referentiel Module — Public API routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/referentiel
| No authentication required — public reference data.
|
*/

// Countries
Route::get('countries', [CountriesController::class, 'index'])->name('countries.index');
Route::get('countries/{iso2}', [CountriesController::class, 'show'])->name('countries.show');
Route::get('countries/{iso2}/regions', [CountriesController::class, 'regions'])->name('countries.regions');

// Regions
Route::get('regions/{uuid}', [RegionsController::class, 'show'])->name('regions.show');
Route::get('regions/{uuid}/cities', [RegionsController::class, 'cities'])->name('regions.cities');

// Cities
Route::get('cities/{uuid}', [CitiesController::class, 'show'])->name('cities.show');
