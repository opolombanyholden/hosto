<?php

declare(strict_types=1);

namespace App\Modules\Lab\Http\Controllers;

use App\Modules\Lab\Models\LabResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lab results API.
 */
final class LabController
{
    /**
     * List results for a patient (authenticated).
     */
    public function patientResults(Request $request): JsonResponse
    {
        $results = LabResult::where('patient_id', $request->user()->id)
            ->with(['laboratory', 'examRequest', 'items'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            /** @phpstan-ignore-next-line return.type */
            'data' => $results->map(fn ($r) => [
                'uuid' => $r->uuid,
                'reference' => $r->reference,
                'status' => $r->status,
                'exam_type' => $r->examRequest->exam_type,
                'laboratory' => ['name' => $r->laboratory->name, 'uuid' => $r->laboratory->uuid],
                'conclusion' => $r->conclusion,
                'validated_at' => $r->validated_at?->toIso8601String(),
                'items' => $r->items->map(fn ($i) => [
                    'test_name' => $i->test_name,
                    'value' => $i->value,
                    'unit' => $i->unit,
                    'reference_range' => $i->reference_range,
                    'flag' => $i->flag,
                ]),
            ]),
            'meta' => ['total' => $results->total()],
        ]);
    }

    /**
     * Single result detail.
     */
    public function show(string $uuid): JsonResponse
    {
        $result = LabResult::whereUuid($uuid)
            ->with(['laboratory', 'examRequest', 'items', 'patient'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'uuid' => $result->uuid,
                'reference' => $result->reference,
                'status' => $result->status,
                'exam_type' => $result->examRequest->exam_type,
                'laboratory' => ['name' => $result->laboratory->name],
                'patient' => ['name' => $result->patient->name],
                'conclusion' => $result->conclusion,
                'notes' => $result->notes,
                'sample_collected_at' => $result->sample_collected_at?->toIso8601String(),
                'completed_at' => $result->completed_at?->toIso8601String(),
                'validated_at' => $result->validated_at?->toIso8601String(),
                'items' => $result->items->map(fn ($i) => [
                    'test_name' => $i->test_name,
                    'test_code' => $i->test_code,
                    'value' => $i->value,
                    'unit' => $i->unit,
                    'reference_range' => $i->reference_range,
                    'flag' => $i->flag,
                    'comment' => $i->comment,
                ]),
            ],
        ]);
    }
}
