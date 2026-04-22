<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\AI\Models\OcrResult;

final class OcrService
{
    /**
     * Process an image through the OCR pipeline.
     *
     * TODO: integrate Tesseract/TensorFlow for real OCR processing.
     */
    public function processImage(int $userId, string $filePath, string $sourceType): OcrResult
    {
        $startTime = hrtime(true);

        // TODO: replace with actual Tesseract/TensorFlow OCR call.
        $simulatedText = 'Simulated OCR output for file: '.basename($filePath);
        $simulatedData = [
            'lines' => [['text' => $simulatedText, 'confidence' => 0.92]],
        ];

        $processingTimeMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        return OcrResult::create([
            'user_id' => $userId,
            'source_type' => $sourceType,
            'file_path' => $filePath,
            'mime_type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'raw_text' => $simulatedText,
            'structured_data' => $simulatedData,
            'confidence_score' => 0.92,
            'status' => 'pending',
            'processing_time_ms' => $processingTimeMs,
        ]);
    }

    /**
     * Retrieve an OCR result by UUID.
     */
    public function getResult(string $uuid): ?OcrResult
    {
        return OcrResult::whereUuid($uuid)->first();
    }
}
