<?php

declare(strict_types=1);

namespace App\Modules\Pro\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\TracksActor;
use App\Modules\RendezVous\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $practitioner_id
 * @property int $patient_id
 * @property int $hosto_id
 * @property int|null $appointment_id
 * @property string $status
 * @property string|null $motif
 * @property string|null $anamnesis
 * @property string|null $examen_clinique
 * @property string|null $diagnostic
 * @property string|null $diagnostic_code
 * @property string|null $conduite_a_tenir
 * @property string|null $notes_internes
 * @property array<string, mixed>|null $vitals
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Practitioner $practitioner
 * @property-read User $patient
 * @property-read Hosto $structure
 * @property-read Appointment|null $appointment
 * @property-read Collection<int, Prescription> $prescriptions
 * @property-read Collection<int, ExamRequest> $examRequests
 */
class Consultation extends Model
{
    use HasUuid;
    use SoftDeletes;
    use TracksActor;

    protected $fillable = [
        'practitioner_id', 'patient_id', 'hosto_id', 'appointment_id', 'status',
        'motif', 'anamnesis', 'examen_clinique', 'diagnostic', 'diagnostic_code',
        'conduite_a_tenir', 'notes_internes', 'vitals', 'started_at', 'completed_at',
    ];

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
     * @return BelongsTo<Hosto, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Hosto::class, 'hosto_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * @return HasMany<ExamRequest, $this>
     */
    public function examRequests(): HasMany
    {
        return $this->hasMany(ExamRequest::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForPractitioner(Builder $query, int $practitionerId): Builder
    {
        return $query->where('practitioner_id', $practitionerId);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vitals' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
