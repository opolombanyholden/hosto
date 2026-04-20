<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $hosto_id
 * @property string $content
 * @property bool $is_approved
 * @property CarbonImmutable|null $approved_at
 * @property string|null $approved_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 * @property-read Hosto $hosto
 */
class HostoRecommendation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = ['user_id', 'hosto_id', 'content', 'is_approved', 'approved_at', 'approved_by'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Hosto, $this>
     */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'immutable_datetime',
        ];
    }
}
