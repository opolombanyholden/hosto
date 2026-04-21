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
 * @property int|null $hosto_id
 * @property string $status
 * @property string $structure_name
 * @property string|null $structure_type
 * @property string|null $structure_city
 * @property string|null $structure_address
 * @property string|null $structure_phone
 * @property string $representative_name
 * @property string|null $representative_role
 * @property string|null $registration_number
 * @property string|null $rejection_reason
 * @property string|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 * @property-read Hosto|null $hosto
 */
class StructureClaim extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'hosto_id', 'status',
        'structure_name', 'structure_type', 'structure_city', 'structure_address', 'structure_phone',
        'representative_name', 'representative_role', 'registration_number',
        'rejection_reason', 'reviewed_by', 'reviewed_at', 'submitted_at',
    ];

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
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
        ];
    }
}
