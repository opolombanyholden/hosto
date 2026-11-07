<?php

declare(strict_types=1);

use App\Modules\Analytic\Http\Controllers\AnalyticController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Health statistics are for authorised decision makers only.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum', 'perm:stats.view'])->group(function (): void {
    Route::get('dashboard', [AnalyticController::class, 'dashboard'])->name('dashboard');
    Route::get('pathologies', [AnalyticController::class, 'pathologies'])->name('pathologies');
    Route::get('alerts', [AnalyticController::class, 'alerts'])->name('alerts');
});
