<?php

declare(strict_types=1);

namespace App\Modules\Mwana\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $patient_id
 * @property int|null $practitioner_id
 * @property int|null $hosto_id
 * @property string $status
 * @property string $due_date
 * @property string|null $actual_delivery_date
 * @property string|null $delivery_type
 * @property int|null $baby_weight_grams
 * @property string|null $baby_gender
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $patient
 * @property-read Practitioner|null $practitioner
 * @property-read Hosto|null $hosto
 * @property-read Collection<int, PrenatalVisit> $prenatalVisits
 */
class Pregnancy extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'practitioner_id', 'hosto_id', 'status',
        'due_date', 'actual_delivery_date', 'delivery_type',
        'baby_weight_grams', 'baby_gender', 'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return BelongsTo<Hosto, $this> */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /** @return HasMany<PrenatalVisit, $this> */
    public function prenatalVisits(): HasMany
    {
        return $this->hasMany(PrenatalVisit::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'actual_delivery_date' => 'date',
        ];
    }
}
