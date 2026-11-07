<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fingerprint and escrow status of a medical encryption key. Never the key.
 *
 * @property int $id
 * @property string $uuid
 * @property string $key_id
 * @property int|null $escrow_shares
 * @property int|null $escrow_threshold
 * @property CarbonImmutable|null $escrowed_at
 * @property CarbonImmutable|null $last_verified_at
 * @property CarbonImmutable|null $retired_at
 */
class MedicalEncryptionKey extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'key_id', 'escrow_shares', 'escrow_threshold', 'escrowed_at', 'last_verified_at', 'retired_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'escrow_shares' => 'integer',
            'escrow_threshold' => 'integer',
            'escrowed_at' => 'immutable_datetime',
            'last_verified_at' => 'immutable_datetime',
            'retired_at' => 'immutable_datetime',
        ];
    }
}
