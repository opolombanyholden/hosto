<?php

declare(strict_types=1);

use App\Modules\Lost\Http\Controllers\LostController;
use Illuminate\Support\Facades\Route;

Route::get('status', [LostController::class, 'status'])->name('status');
