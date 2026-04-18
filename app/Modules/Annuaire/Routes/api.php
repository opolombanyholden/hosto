<?php

declare(strict_types=1);

use App\Modules\Annuaire\Http\Controllers\HostosController;
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

Route::get('hostos', [HostosController::class, 'index'])->name('hostos.index');
Route::get('hostos/{uuid}', [HostosController::class, 'show'])->name('hostos.show');
