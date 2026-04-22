<?php

declare(strict_types=1);

use App\Modules\AI\Http\Controllers\AIController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('ocr/upload', [AIController::class, 'ocrUpload'])->name('ocr.upload');
    Route::post('chat/start', [AIController::class, 'chatStart'])->name('chat.start');
    Route::post('chat/{uuid}/message', [AIController::class, 'chatMessage'])->name('chat.message');
});

Route::get('predictions', [AIController::class, 'predictions'])->name('predictions.index');
