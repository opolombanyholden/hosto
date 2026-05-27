<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\RendezVous\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property int $patient_id
 * @property int $practitioner_id
 * @property int|null $source_appointment_id
 * @property array<string, mixed>|null $scope
 * @property CarbonImmutable $granted_at
 * @property CarbonImmutable|null $revoked_at
 * @property int $access_count
 * @property CarbonImmutable|null $last_accessed_at
 * @property-read Collection<int, MedicalRecordAccessLog> $accessLogs
 */
class MedicalRecordGrant extends Model
{
    use HasUuid;

    protected $fillable = [
        'patient_id', 'practitioner_id', 'source_appointment_id',
        'scope', 'granted_at', 'revoked_at',
        'access_count', 'last_accessed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'access_count' => 0,
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function sourceAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'source_appointment_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(MedicalRecordAccessLog::class, 'grant_id')
            ->orderByDesc('accessed_at');
    }

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_accessed_at' => 'immutable_datetime',
            'access_count' => 'integer',
        ];
    }
}
