<?php

declare(strict_types=1);

use App\Modules\Analytic\Http\Controllers\AnalyticController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [AnalyticController::class, 'dashboard'])->name('dashboard');
Route::get('pathologies', [AnalyticController::class, 'pathologies'])->name('pathologies');
Route::get('alerts', [AnalyticController::class, 'alerts'])->name('alerts');
