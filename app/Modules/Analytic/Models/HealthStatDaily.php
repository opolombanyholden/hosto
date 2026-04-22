<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Models;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Daily aggregated health statistics.
 *
 * @property int $id
 * @property Carbon $date
 * @property int|null $hosto_id
 * @property int|null $region_id
 * @property int $consultations_count
 * @property int $teleconsultations_count
 * @property int $appointments_count
 * @property int $prescriptions_count
 * @property int $exams_count
 * @property int $hospitalizations_count
 * @property int $births_count
 * @property int $deaths_count
 * @property int $vaccinations_count
 */
class HealthStatDaily extends Model
{
    public $timestamps = false;

    protected $table = 'health_stats_daily';

    protected $fillable = [
        'date', 'hosto_id', 'region_id',
        'consultations_count', 'teleconsultations_count', 'appointments_count',
        'prescriptions_count', 'exams_count', 'hospitalizations_count',
        'births_count', 'deaths_count', 'vaccinations_count',
    ];

    /** @return BelongsTo<Hosto, $this> */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
