<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $admin_user_id
 * @property int $target_user_id
 * @property string $reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class ImpersonationSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'admin_user_id', 'target_user_id', 'reason',
        'started_at', 'ended_at', 'ip_address', 'user_agent',
    ];

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
