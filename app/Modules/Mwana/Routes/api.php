<?php

declare(strict_types=1);

use App\Modules\Mwana\Http\Controllers\MwanaController;
use Illuminate\Support\Facades\Route;

Route::get('status', [MwanaController::class, 'status'])->name('status');
