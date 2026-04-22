<?php

declare(strict_types=1);

namespace App\Modules\Sync\Services;

use App\Modules\Sync\Models\SyncLog;
use App\Modules\Sync\Models\SyncQueueItem;
use Illuminate\Support\Str;

/**
 * SyncService.
 *
 * Manages the synchronization queue between local and cloud instances.
 *
 * In LOCAL mode:
 *   - Enqueues changes to sync_queue when entities are created/updated
 *   - Push: sends pending queue items to the cloud API
 *   - Pull: fetches recent changes from the cloud
 *
 * In CLOUD mode:
 *   - Receives pushed data from local instances
 *   - Serves pull requests with recent changes
 *
 * @see docs/adr/0014-synchronisation-offline.md
 */
final class SyncService
{
    /**
     * Enqueue an entity change for future sync (local mode only).
     *
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(string $entityType, string $entityUuid, string $action, array $payload = []): void
    {
        if (config('hosto.deployment') !== 'local') {
            return;
        }

        SyncQueueItem::create([
            'uuid' => (string) Str::uuid7(),
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'action' => $action,
            'payload' => $payload,
            'origin' => config('hosto.structure_uuid', 'unknown'),
            'sync_version' => now()->getTimestampMs(),
            'status' => 'pending',
        ]);
    }

    /**
     * Get pending items count.
     */
    public function pendingCount(): int
    {
        return SyncQueueItem::pending()->count();
    }

    /**
     * Get sync status summary.
     *
     * @return array{pending: int, last_sync: array<string, mixed>|null, deployment: string}
     */
    public function status(): array
    {
        $lastSync = SyncLog::orderByDesc('started_at')->first();

        return [
            'pending' => $this->pendingCount(),
            'last_sync' => $lastSync ? [
                'direction' => $lastSync->direction,
                'status' => $lastSync->status,
                'entities_pushed' => $lastSync->entities_pushed,
                'entities_pulled' => $lastSync->entities_pulled,
                'conflicts' => $lastSync->conflicts,
                'completed_at' => $lastSync->completed_at,
                'duration_ms' => $lastSync->duration_ms,
            ] : null,
            'deployment' => (string) config('hosto.deployment', 'cloud'),
        ];
    }
}
