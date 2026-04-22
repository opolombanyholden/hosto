<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Controllers;

use App\Modules\Sync\Models\SyncLog;
use App\Modules\Sync\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SyncController
{
    public function __construct(
        private readonly SyncService $sync,
    ) {}

    /**
     * Sync status.
     */
    public function status(): JsonResponse
    {
        return response()->json(['data' => $this->sync->status()]);
    }

    /**
     * Push: local instance sends pending changes to cloud.
     */
    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.entity_type' => 'required|string',
            'items.*.entity_uuid' => 'required|string',
            'items.*.action' => 'required|in:create,update,delete',
            'items.*.payload' => 'nullable|array',
            'items.*.origin' => 'required|string',
            'items.*.sync_version' => 'required|integer',
        ]);

        $log = SyncLog::create([
            'uuid' => (string) Str::uuid7(),
            'direction' => 'push',
            'status' => 'started',
            'started_at' => now(),
        ]);

        $pushed = 0;
        $conflicts = 0;

        foreach ($data['items'] as $item) {
            // TODO: apply the change to the cloud database.
            // For now, log the received item.
            $pushed++;
        }

        $log->update([
            'status' => 'completed',
            'entities_pushed' => $pushed,
            'conflicts' => $conflicts,
            'completed_at' => now(),
            'duration_ms' => (int) (microtime(true) * 1000) - (int) ($log->started_at ? strtotime($log->started_at) * 1000 : 0),
        ]);

        return response()->json([
            'data' => ['pushed' => $pushed, 'conflicts' => $conflicts, 'status' => 'completed'],
        ]);
    }

    /**
     * Pull: local instance fetches recent changes from cloud.
     */
    public function pull(Request $request): JsonResponse
    {
        $since = $request->input('since'); // ISO timestamp
        $limit = $request->integer('limit', 100);

        // TODO: query recently modified syncable entities since $since.
        // Return serialized entities for the local instance to apply.

        return response()->json([
            'data' => [
                'items' => [],
                'count' => 0,
                'has_more' => false,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
