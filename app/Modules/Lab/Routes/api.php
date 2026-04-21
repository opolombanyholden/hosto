<?php

declare(strict_types=1);

use App\Modules\Lab\Http\Controllers\LabController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Authenticated: patient views their own results.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function (): void {
    Route::get('results', [LabController::class, 'patientResults'])->name('results.index');
});

// Public detail (for sharing via reference — access controlled by UUID knowledge).
Route::get('results/{uuid}', [LabController::class, 'show'])->name('results.show');
