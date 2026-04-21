<?php

declare(strict_types=1);

use App\Modules\Telecon\Http\Controllers\TeleconController;
use Illuminate\Support\Facades\Route;

Route::get('sessions/{uuid}', [TeleconController::class, 'show'])->name('sessions.show');
