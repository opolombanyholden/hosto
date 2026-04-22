<?php

declare(strict_types=1);

namespace App\Modules\EVax\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $patient_id
 * @property string $vaccine_name
 * @property string|null $vaccine_code
 * @property int $dose_number
 * @property string $administered_at
 * @property int|null $administered_by_id
 * @property int|null $hosto_id
 * @property string|null $batch_number
 * @property string|null $next_dose_date
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $patient
 * @property-read User|null $administeredBy
 * @property-read Hosto|null $hosto
 */
class VaccinationRecord extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'vaccine_name', 'vaccine_code', 'dose_number',
        'administered_at', 'administered_by_id', 'hosto_id',
        'batch_number', 'next_dose_date', 'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<User, $this> */
    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by_id');
    }

    /** @return BelongsTo<Hosto, $this> */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'administered_at' => 'date',
            'next_dose_date' => 'date',
        ];
    }
}
