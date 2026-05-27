<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\TracksActor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $time_slot_id
 * @property int $patient_id
 * @property int $practitioner_id
 * @property int $hosto_id
 * @property string $status
 * @property string|null $reason
 * @property string|null $notes
 * @property string $appointment_type
 * @property string $consultation_mode
 * @property bool $share_medical_record
 * @property int|null $third_party_user_id
 * @property string|null $visit_address
 * @property string|null $visit_lat
 * @property string|null $visit_lng
 * @property CarbonImmutable|null $visit_geocoded_at
 * @property int|null $visit_location_accuracy_m
 * @property CarbonImmutable|null $requested_at
 * @property-read bool $is_teleconsultation
 * @property string|null $cancellation_reason
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancelled_by
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read TimeSlot $timeSlot
 * @property-read User $patient
 * @property-read Practitioner $practitioner
 * @property-read Hosto $structure
 */
class Appointment extends Model
{
    use HasUuid;
    use SoftDeletes;
    use TracksActor;

    protected $fillable = [
        'time_slot_id', 'patient_id', 'practitioner_id', 'hosto_id',
        'specialty_code', 'status', 'reason', 'notes',
        'cancellation_reason', 'cancelled_at', 'cancelled_by',
        'confirmed_at', 'completed_at',
        'is_for_third_party', 'third_party_name', 'third_party_age',
        'third_party_gender', 'third_party_relation', 'third_party_address',
        'third_party_city', 'third_party_phone', 'third_party_notes',
        'appointment_type', 'consultation_mode', 'share_medical_record',
        'third_party_user_id', 'visit_address', 'visit_lat', 'visit_lng',
        'visit_geocoded_at', 'visit_location_accuracy_m', 'requested_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<TimeSlot, $this>
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return BelongsTo<Practitioner, $this>
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /**
     * @return BelongsTo<Hosto, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Hosto::class, 'hosto_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('timeSlot', fn ($q) => $q->where('date', '>=', now()->toDateString()));
    }

    public function getIsTeleconsultationAttribute(): bool
    {
        return $this->consultation_mode === 'telecon';
    }

    public function isUrgent(): bool
    {
        return $this->appointment_type === 'urgence';
    }

    public function isHomeVisit(): bool
    {
        return $this->consultation_mode === 'home';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancelled_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'reminder_j1_sent' => 'boolean',
            'reminder_h2_sent' => 'boolean',
            'share_medical_record' => 'boolean',
            'visit_lat' => 'decimal:7',
            'visit_lng' => 'decimal:7',
            'visit_geocoded_at' => 'immutable_datetime',
            'visit_location_accuracy_m' => 'integer',
            'requested_at' => 'immutable_datetime',
        ];
    }
}
