<?php

declare(strict_types=1);

namespace App\Modules\HostoPlus\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $plan_type
 * @property string $status
 * @property int $amount
 * @property string $currency_code
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $expires_at
 * @property bool $auto_renew
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 */
class Subscription extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'plan_type', 'status', 'amount',
        'currency_code', 'started_at', 'expires_at', 'auto_renew',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'auto_renew' => 'boolean',
        ];
    }
}
