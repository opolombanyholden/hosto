<?php

declare(strict_types=1);

use App\Modules\Annuaire\Http\Controllers\HostosController;
use App\Modules\Annuaire\Http\Controllers\PractitionersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Annuaire Module — Public API routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/annuaire
| No authentication required — public health directory.
|
*/

// Structures
Route::get('hostos', [HostosController::class, 'index'])->name('hostos.index');
Route::get('hostos/{uuid}', [HostosController::class, 'show'])->name('hostos.show');

// Practitioners
Route::get('practitioners', [PractitionersController::class, 'index'])->name('practitioners.index');
Route::get('practitioners/{slug}', [PractitionersController::class, 'show'])->name('practitioners.show');
