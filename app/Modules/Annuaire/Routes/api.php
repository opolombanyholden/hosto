<?php

declare(strict_types=1);

use App\Modules\Annuaire\Http\Controllers\HostosController;
use App\Modules\Annuaire\Http\Controllers\InteractionsController;
use App\Modules\Annuaire\Http\Controllers\PractitionersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Annuaire Module — API routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/annuaire
|
*/

// Structures (public)
Route::get('hostos', [HostosController::class, 'index'])->name('hostos.index');
Route::get('hostos/{uuid}', [HostosController::class, 'show'])->name('hostos.show');

// Practitioners (public)
Route::get('practitioners', [PractitionersController::class, 'index'])->name('practitioners.index');
Route::get('practitioners/{slug}', [PractitionersController::class, 'show'])->name('practitioners.show');

// Recommendations (public read)
Route::get('hostos/{uuid}/recommendations', [InteractionsController::class, 'recommendations'])->name('hostos.recommendations');

// Interactions (authenticated)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('hostos/{uuid}/like', [InteractionsController::class, 'toggleLike'])->name('hostos.like');
    Route::get('hostos/{uuid}/like-status', [InteractionsController::class, 'likeStatus'])->name('hostos.like-status');
    Route::post('hostos/{uuid}/recommend', [InteractionsController::class, 'recommend'])->name('hostos.recommend');
});
