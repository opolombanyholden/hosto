<?php

declare(strict_types=1);

namespace App\Modules\Mwana\Models;

use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $pregnancy_id
 * @property int $practitioner_id
 * @property string $visit_date
 * @property int $week_of_pregnancy
 * @property float|null $weight_kg
 * @property string|null $blood_pressure
 * @property int|null $baby_heartbeat
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Pregnancy $pregnancy
 * @property-read Practitioner $practitioner
 */
class PrenatalVisit extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'pregnancy_id', 'practitioner_id', 'visit_date',
        'week_of_pregnancy', 'weight_kg', 'blood_pressure',
        'baby_heartbeat', 'notes',
    ];

    /** @return BelongsTo<Pregnancy, $this> */
    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'weight_kg' => 'decimal:2',
        ];
    }
}
