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
 * @property int|null $patient_id
 * @property int|null $dependent_id
 * @property string $vaccine_name
 * @property string|null $vaccine_code
 * @property int|null $vaccine_id
 * @property bool $is_standardized
 * @property int $dose_number
 * @property CarbonImmutable $administered_at
 * @property int|null $administered_by_id
 * @property int|null $hosto_id
 * @property CarbonImmutable|null $signed_at
 * @property string|null $signed_by_signature
 * @property int $carnet_revision
 * @property string|null $batch_number
 * @property CarbonImmutable|null $next_dose_date
 * @property string|null $notes
 */
class VaccinationRecord extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'dependent_id', 'vaccine_name', 'vaccine_code',
        'vaccine_id', 'is_standardized', 'dose_number',
        'administered_at', 'administered_by_id', 'hosto_id',
        'signed_at', 'signed_by_signature', 'carnet_revision',
        'batch_number', 'next_dose_date', 'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<Dependent, $this> */
    public function dependent(): BelongsTo
    {
        return $this->belongsTo(Dependent::class, 'dependent_id');
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

    /** @return BelongsTo<Vaccine, $this> */
    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'administered_at' => 'immutable_date',
            'next_dose_date' => 'immutable_date',
            'signed_at' => 'immutable_datetime',
            'is_standardized' => 'boolean',
            'dose_number' => 'integer',
            'carnet_revision' => 'integer',
        ];
    }
}
