<?php

declare(strict_types=1);

namespace App\Modules\Lost\Models;

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
 * @property string $type
 * @property int|null $patient_id
 * @property int $declared_by_id
 * @property int|null $hosto_id
 * @property string $declaration_date
 * @property string $person_name
 * @property string|null $person_gender
 * @property string|null $person_birth_date
 * @property string|null $person_death_date
 * @property string|null $cause_of_death
 * @property string|null $certificate_number
 * @property string $status
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $declaredBy
 * @property-read User|null $patient
 * @property-read Hosto|null $hosto
 */
class VitalDeclaration extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'type', 'patient_id', 'declared_by_id', 'hosto_id',
        'declaration_date', 'person_name', 'person_gender',
        'person_birth_date', 'person_death_date', 'cause_of_death',
        'certificate_number', 'status', 'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
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
            'declaration_date' => 'date',
            'person_birth_date' => 'date',
            'person_death_date' => 'date',
        ];
    }
}
