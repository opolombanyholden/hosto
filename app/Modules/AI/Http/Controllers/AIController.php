<?php

declare(strict_types=1);

namespace App\Modules\AI\Http\Controllers;

use App\Modules\AI\Models\ChatbotConversation;
use App\Modules\AI\Services\ChatbotService;
use App\Modules\AI\Services\EpiPredictionService;
use App\Modules\AI\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class AIController
{
    public function __construct(
        private readonly OcrService $ocrService,
        private readonly ChatbotService $chatbotService,
        private readonly EpiPredictionService $epiPredictionService,
    ) {}

    /**
     * Upload an image for OCR processing.
     */
    public function ocrUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
            'source_type' => ['required', 'string', 'max:50'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('ocr-uploads', 'local');

        $result = $this->ocrService->processImage(
            (int) $request->user()->id,
            storage_path('app/'.$path),
            $request->input('source_type'),
        );

        return response()->json(['data' => $result], 201);
    }

    /**
     * Start a new chatbot conversation.
     */
    public function chatStart(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => ['required', 'string', 'max:255'],
        ]);

        $conversation = $this->chatbotService->startConversation(
            (int) $request->user()->id,
            $request->input('topic'),
        );

        return response()->json(['data' => $conversation], 201);
    }

    /**
     * Send a message within an existing conversation.
     */
    public function chatMessage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = ChatbotConversation::whereUuid($uuid)->firstOrFail();

        $message = $this->chatbotService->sendMessage(
            $conversation,
            $request->input('message'),
        );

        return response()->json(['data' => $message], 201);
    }

    /**
     * Return latest epidemiological predictions.
     */
    public function predictions(): JsonResponse
    {
        $predictions = $this->epiPredictionService->latestPredictions();

        return response()->json(['data' => $predictions]);
    }
}
