<?php

declare(strict_types=1);

use App\Modules\EVax\Http\Controllers\EVaxController;
use Illuminate\Support\Facades\Route;

Route::get('status', [EVaxController::class, 'status'])->name('status');
