<?php

declare(strict_types=1);

use App\Modules\Connect\Http\Controllers\ConnectController;
use Illuminate\Support\Facades\Route;

Route::get('status', [ConnectController::class, 'status'])->name('status');
