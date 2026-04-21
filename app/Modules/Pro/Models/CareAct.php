<?php

declare(strict_types=1);

namespace App\Modules\Pro\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CareAct — a medical care act (soin).
 *
 * Step 5 in the consultation workflow. Optional.
 * Examples: injection, perfusion, pansement, suture, kine, dialyse.
 *
 * @property int $id
 * @property string $uuid
 * @property int $consultation_id
 * @property int $practitioner_id
 * @property int|null $performed_by_id
 * @property int $patient_id
 * @property string $care_type
 * @property string $description
 * @property string|null $instructions
 * @property string $status
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $performed_at
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class CareAct extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'consultation_id', 'practitioner_id', 'performed_by_id', 'patient_id',
        'care_type', 'description', 'instructions', 'status',
        'scheduled_at', 'performed_at', 'notes',
    ];

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * @return BelongsTo<Practitioner, $this>
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /**
     * @return BelongsTo<Practitioner, $this>
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class, 'performed_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'performed_at' => 'immutable_datetime',
        ];
    }
}
