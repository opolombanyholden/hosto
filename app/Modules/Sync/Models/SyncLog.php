<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $direction
 * @property string $status
 * @property int $entities_pushed
 * @property int $entities_pulled
 * @property int $conflicts
 * @property int $errors
 * @property string|null $details
 * @property string $started_at
 * @property string|null $completed_at
 * @property int|null $duration_ms
 */
class SyncLog extends Model
{
    public $timestamps = false;

    protected $table = 'sync_log';

    protected $fillable = [
        'uuid', 'direction', 'status', 'entities_pushed', 'entities_pulled',
        'conflicts', 'errors', 'details', 'started_at', 'completed_at', 'duration_ms',
    ];
}
