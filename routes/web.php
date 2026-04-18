<?php

declare(strict_types=1);

use App\Http\Controllers\AnnuaireWebController;
use App\Modules\Core\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public
// ---------------------------------------------------------------

Route::get('/', function () {
    return view('welcome');
});

Route::get('/annuaire', [AnnuaireWebController::class, 'index'])->name('annuaire.index');
Route::get('/annuaire/{slug}', [AnnuaireWebController::class, 'show'])->name('annuaire.show');

// ---------------------------------------------------------------
// Auth : Usager (patient)
// ---------------------------------------------------------------

Route::prefix('compte')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'compteConnexionForm'])->name('compte.connexion');
        Route::post('/connexion', [AuthController::class, 'compteConnexion']);
        Route::get('/inscription', [AuthController::class, 'compteInscriptionForm'])->name('compte.inscription');
        Route::post('/inscription', [AuthController::class, 'compteInscription']);
    });

    Route::middleware(['auth', 'env:usager'])->group(function (): void {
        Route::get('/', function () {
            return view('compte.dashboard');
        })->name('compte.dashboard');
    });
});

// ---------------------------------------------------------------
// Auth : Professionnel
// ---------------------------------------------------------------

Route::prefix('pro')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'proConnexionForm'])->name('pro.connexion');
        Route::post('/connexion', [AuthController::class, 'proConnexion']);
        Route::get('/inscription', [AuthController::class, 'proInscriptionForm'])->name('pro.inscription');
        Route::post('/inscription', [AuthController::class, 'proInscription']);
    });

    Route::middleware(['auth', 'env:pro'])->group(function (): void {
        Route::get('/', function () {
            return view('pro.dashboard');
        })->name('pro.dashboard');
    });
});

// ---------------------------------------------------------------
// Auth : Admin
// ---------------------------------------------------------------

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'adminConnexionForm'])->name('admin.connexion');
        Route::post('/connexion', [AuthController::class, 'adminConnexion']);
    });

    Route::middleware(['auth', 'env:admin'])->group(function (): void {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
    });
});

// ---------------------------------------------------------------
// Logout (shared)
// ---------------------------------------------------------------

Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
