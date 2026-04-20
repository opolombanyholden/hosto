<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $hosto_id
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read Hosto $hosto
 */
class HostoLike extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'hosto_id'];

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
