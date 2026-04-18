<?php

declare(strict_types=1);

use App\Modules\Referentiel\Http\Controllers\CitiesController;
use App\Modules\Referentiel\Http\Controllers\CountriesController;
use App\Modules\Referentiel\Http\Controllers\RegionsController;
use App\Modules\Referentiel\Http\Controllers\ServicesController;
use App\Modules\Referentiel\Http\Controllers\SpecialtiesController;
use App\Modules\Referentiel\Http\Controllers\StructureTypesController;
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

// Structure types
Route::get('structure-types', [StructureTypesController::class, 'index'])->name('structure-types.index');
Route::get('structure-types/{slug}', [StructureTypesController::class, 'show'])->name('structure-types.show');

// Specialties
Route::get('specialties', [SpecialtiesController::class, 'index'])->name('specialties.index');
Route::get('specialties/{uuid}', [SpecialtiesController::class, 'show'])->name('specialties.show');

// Services / prestations / soins
Route::get('services', [ServicesController::class, 'index'])->name('services.index');
