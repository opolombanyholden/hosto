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
 * @property bool $is_teleconsultation
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
        'status', 'reason', 'notes', 'is_teleconsultation',
        'cancellation_reason', 'cancelled_at', 'cancelled_by',
        'confirmed_at', 'completed_at',
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_teleconsultation' => 'boolean',
            'cancelled_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'reminder_j1_sent' => 'boolean',
            'reminder_h2_sent' => 'boolean',
        ];
    }
}
