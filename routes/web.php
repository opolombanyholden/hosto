<?php

declare(strict_types=1);

use App\Http\Controllers\AnnuaireWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/annuaire', [AnnuaireWebController::class, 'index'])->name('annuaire.index');
Route::get('/annuaire/{slug}', [AnnuaireWebController::class, 'show'])->name('annuaire.show');
