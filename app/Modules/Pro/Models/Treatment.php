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
 * Treatment — a therapeutic plan.
 *
 * Step 6 in the consultation workflow. Optional.
 * Examples: diet, rest, rehabilitation, follow-up, lifestyle changes.
 *
 * Different from Prescription: treatment is "what to do",
 * prescription is "what medication to take".
 *
 * @property int $id
 * @property string $uuid
 * @property int $consultation_id
 * @property int $practitioner_id
 * @property int $patient_id
 * @property string $type
 * @property string $description
 * @property string|null $instructions
 * @property string|null $frequency
 * @property string|null $duration
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Treatment extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'consultation_id', 'practitioner_id', 'patient_id',
        'type', 'description', 'instructions', 'frequency', 'duration',
        'start_date', 'end_date', 'status',
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
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
