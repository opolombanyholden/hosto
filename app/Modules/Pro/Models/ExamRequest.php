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
 * @property int $id
 * @property string $uuid
 * @property int $consultation_id
 * @property int $practitioner_id
 * @property int $patient_id
 * @property string $status
 * @property string $exam_type
 * @property string|null $clinical_info
 * @property string $urgency
 * @property string|null $results
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Consultation $consultation
 * @property-read Practitioner $practitioner
 * @property-read User $patient
 */
class ExamRequest extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'consultation_id', 'practitioner_id', 'patient_id',
        'status', 'exam_type', 'clinical_info', 'urgency',
        'results', 'scheduled_at', 'completed_at',
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
            'scheduled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
