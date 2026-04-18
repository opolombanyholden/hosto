<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Models;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use Carbon\Carbon;
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
 * @property int $hosto_id
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property int $duration_minutes
 * @property bool $is_available
 * @property bool $is_teleconsultation
 * @property int|null $fee
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Practitioner $practitioner
 * @property-read Hosto $structure
 * @property-read Collection<int, Appointment> $appointments
 */
class TimeSlot extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'practitioner_id', 'hosto_id', 'date', 'start_time', 'end_time',
        'duration_minutes', 'is_available', 'is_teleconsultation', 'fee',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
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
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)->where('date', '>=', now()->toDateString());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
            'is_teleconsultation' => 'boolean',
            'duration_minutes' => 'integer',
            'fee' => 'integer',
        ];
    }
}
