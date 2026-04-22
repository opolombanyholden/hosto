<?php

declare(strict_types=1);

use App\Modules\HostoPlus\Http\Controllers\HostoPlusController;
use Illuminate\Support\Facades\Route;

Route::get('status', [HostoPlusController::class, 'status'])->name('status');
