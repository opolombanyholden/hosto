<?php
declare(strict_types=1);

namespace App\Modules\EVax\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $date_of_birth
 * @property string|null $gender
 * @property string|null $nip
 * @property string|null $notes
 * @property string $carnet_qr_secret
 */
class Dependent extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'date_of_birth',
        'gender', 'nip', 'notes', 'carnet_qr_secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $d): void {
            if (empty($d->carnet_qr_secret)) {
                $d->carnet_qr_secret = self::generateSecret();
            }
        });
    }

    public static function generateSecret(): string
    {
        return Str::random(32);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<VaccinationRecord, $this> */
    public function vaccinationRecords(): HasMany
    {
        return $this->hasMany(VaccinationRecord::class, 'dependent_id')
            ->orderBy('administered_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date_of_birth' => 'immutable_date'];
    }
}
