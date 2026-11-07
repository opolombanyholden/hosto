<?php

declare(strict_types=1);

use App\Modules\Telecon\Http\Controllers\TeleconController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// The room name grants access to the video call: participants only.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function (): void {
    Route::get('sessions/{uuid}', [TeleconController::class, 'show'])->name('sessions.show');
});
