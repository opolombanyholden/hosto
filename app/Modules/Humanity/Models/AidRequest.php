<?php

declare(strict_types=1);

namespace App\Modules\Humanity\Models;

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
 * @property string $type
 * @property string $title
 * @property string $description
 * @property string $urgency
 * @property int|null $target_amount
 * @property int $collected_amount
 * @property string $status
 * @property string|null $blood_type
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 */
class AidRequest extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'title', 'description', 'urgency',
        'target_amount', 'collected_amount', 'status', 'blood_type',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
