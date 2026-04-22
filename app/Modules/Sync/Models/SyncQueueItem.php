<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $entity_type
 * @property string $entity_uuid
 * @property string $action
 * @property array<string, mixed>|null $payload
 * @property string $origin
 * @property int $sync_version
 * @property string $status
 * @property string|null $error_message
 * @property int $attempts
 */
class SyncQueueItem extends Model
{
    public $timestamps = false;

    protected $table = 'sync_queue';

    protected $fillable = [
        'uuid', 'entity_type', 'entity_uuid', 'action', 'payload',
        'origin', 'sync_version', 'status', 'error_message', 'attempts',
        'created_at', 'synced_at',
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
