<?php

declare(strict_types=1);

use App\Modules\RendezVous\Http\Controllers\AppointmentsController;
use App\Modules\RendezVous\Http\Controllers\TimeSlotsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RendezVous Module — API routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/rdv
|
*/

// Public: browse available slots (no auth required).
Route::get('slots', [TimeSlotsController::class, 'index'])->name('slots.index');

// Authenticated: manage appointments.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('appointments', [AppointmentsController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentsController::class, 'store'])->name('appointments.store');
    Route::get('appointments/{uuid}', [AppointmentsController::class, 'show'])->name('appointments.show');
    Route::post('appointments/{uuid}/cancel', [AppointmentsController::class, 'cancel'])->name('appointments.cancel');
});
