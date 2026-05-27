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
 * @property int $inviter_user_id
 * @property string $context
 * @property int|null $context_id
 * @property string $phone_normalized
 * @property string $token
 * @property string|null $sent_via
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $accepted_at
 * @property int|null $accepted_user_id
 * @property CarbonImmutable $expires_at
 */
class InvitationLink extends Model
{
    use HasUuid;

    protected $fillable = [
        'inviter_user_id', 'context', 'context_id',
        'phone_normalized', 'token', 'sent_via',
        'sent_at', 'accepted_at', 'accepted_user_id', 'expires_at',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
