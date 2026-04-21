<?php

declare(strict_types=1);

use App\Modules\Pharma\Http\Controllers\PharmacyController;
use Illuminate\Support\Facades\Route;

// Public: check medication availability at pharmacies.
Route::get('stock', [PharmacyController::class, 'stock'])->name('stock.index');
Route::get('pharmacies/{uuid}/stock', [PharmacyController::class, 'pharmacyStock'])->name('pharmacy.stock');
