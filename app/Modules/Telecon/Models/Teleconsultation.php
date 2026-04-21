<?php

declare(strict_types=1);

namespace App\Modules\Telecon\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Pro\Models\Consultation;
use App\Modules\RendezVous\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Teleconsultation session backed by Jitsi Meet.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $appointment_id
 * @property int|null $consultation_id
 * @property int $practitioner_id
 * @property int $patient_id
 * @property string $room_name
 * @property string $jitsi_domain
 * @property string $status
 * @property CarbonImmutable $scheduled_at
 * @property int $duration_minutes
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $ended_at
 * @property int|null $actual_duration_minutes
 * @property bool $practitioner_joined
 * @property bool $patient_joined
 * @property bool $recording_consent_practitioner
 * @property bool $recording_consent_patient
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Teleconsultation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id', 'consultation_id', 'practitioner_id', 'patient_id',
        'room_name', 'jitsi_domain', 'status', 'scheduled_at', 'duration_minutes',
        'started_at', 'ended_at', 'actual_duration_minutes',
        'practitioner_joined', 'patient_joined',
        'recording_consent_practitioner', 'recording_consent_patient', 'notes',
    ];

    /**
     * Generate a secure, non-guessable Jitsi room name.
     */
    public static function generateRoomName(): string
    {
        return 'hosto-'.Str::uuid7();
    }

    /**
     * Build the full Jitsi Meet URL for this session.
     */
    public function jitsiUrl(?string $displayName = null): string
    {
        $url = "https://{$this->jitsi_domain}/{$this->room_name}";

        $params = [
            'config.prejoinPageEnabled' => 'true',
            'config.startWithAudioMuted' => 'false',
            'config.startWithVideoMuted' => 'false',
            'config.disableDeepLinking' => 'true',
        ];

        if ($displayName) {
            $params['userInfo.displayName'] = $displayName;
        }

        return $url.'#'.http_build_query($params);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return BelongsTo<User, $this> */
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
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'practitioner_joined' => 'boolean',
            'patient_joined' => 'boolean',
            'recording_consent_practitioner' => 'boolean',
            'recording_consent_patient' => 'boolean',
        ];
    }
}
