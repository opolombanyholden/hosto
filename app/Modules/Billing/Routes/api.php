<?php

declare(strict_types=1);

use App\Modules\Billing\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Public: available payment methods.
Route::get('payment-methods', [BillingController::class, 'paymentMethods'])->name('payment-methods');

// Authenticated: patient billing.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function (): void {
    Route::get('invoices', [BillingController::class, 'invoices'])->name('invoices.index');
    Route::get('insurance-cards', [BillingController::class, 'insuranceCards'])->name('insurance-cards.index');
});
