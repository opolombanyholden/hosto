<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Module — API routes (mounted under /api/v1/core)
|--------------------------------------------------------------------------
*/

Route::get('health/live', [HealthController::class, 'live'])->name('health.live');
Route::get('health/ready', [HealthController::class, 'ready'])->name('health.ready');
