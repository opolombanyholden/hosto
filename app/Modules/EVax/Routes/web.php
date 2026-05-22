<?php

declare(strict_types=1);

use App\Modules\EVax\Http\Controllers\EVaxPatientController;
use App\Modules\EVax\Http\Controllers\EVaxProController;
use App\Modules\EVax\Http\Controllers\EVaxPublicController;
use Illuminate\Support\Facades\Route;

// Public verification (no auth)
Route::get('/c/v/{secret}', [EVaxPublicController::class, 'verify'])->name('evax.public.verify');
Route::get('/carnet/identity/{secret}', [EVaxPublicController::class, 'identity'])->name('evax.public.identity');
Route::get('/.well-known/hosto/carnet-keys.json', [EVaxPublicController::class, 'jwks'])->name('evax.public.jwks');

// Patient (auth required)
Route::middleware('auth')->prefix('compte/carnet-vaccination')->name('evax.patient.')->group(function (): void {
    Route::get('/', [EVaxPatientController::class, 'myCarnet'])->name('mine');
    Route::get('/dependents', [EVaxPatientController::class, 'dependentsIndex'])->name('dependents.index');
    Route::post('/dependents', [EVaxPatientController::class, 'storeDependent'])->name('dependents.store');
    Route::put('/dependents/{uuid}', [EVaxPatientController::class, 'updateDependent'])->name('dependents.update');
    Route::delete('/dependents/{uuid}', [EVaxPatientController::class, 'destroyDependent'])->name('dependents.destroy');
    Route::get('/dependent/{uuid}', [EVaxPatientController::class, 'dependentCarnet'])->name('dependent.show');
    Route::get('/{target}/pdf', [EVaxPatientController::class, 'downloadPdf'])->name('pdf');
});

// Pro (auth required, controller enforces verified pro)
Route::middleware('auth')->prefix('pro/evax')->name('evax.pro.')->group(function (): void {
    Route::get('/', fn () => view('evax::pro.search'))->name('home');
    Route::get('/search', [EVaxProController::class, 'searchPatient'])->name('search');
    Route::post('/resolve-qr', [EVaxProController::class, 'resolvePatientFromQr'])->name('resolve-qr');
    Route::get('/vaccinations/new', [EVaxProController::class, 'showAddForm'])->name('vaccinations.new');
    Route::post('/vaccinations', [EVaxProController::class, 'storeVaccination'])->name('vaccinations.store');
});
