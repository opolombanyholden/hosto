<?php

declare(strict_types=1);

use App\Modules\Humanity\Http\Controllers\HumanityController;
use Illuminate\Support\Facades\Route;

Route::get('status', [HumanityController::class, 'status'])->name('status');
