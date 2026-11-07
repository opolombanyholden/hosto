<?php

declare(strict_types=1);

use App\Modules\Sync\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

// Sync status (public — used by monitoring).
Route::get('status', [SyncController::class, 'status'])->name('status');

// Push/pull: local instances only (ADR 0014), identified by a token holding sync.manage.
Route::middleware(['auth:sanctum', 'perm:sync.manage'])->group(function (): void {
    Route::post('push', [SyncController::class, 'push'])->name('push');
    Route::get('pull', [SyncController::class, 'pull'])->name('pull');
});
