<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Region;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Epidemiological alert triggered when a pathology crosses a threshold.
 *
 * @property int $id
 * @property string $uuid
 * @property string $alert_type
 * @property string $diagnostic_code
 * @property string $diagnostic_label
 * @property int|null $region_id
 * @property string $severity
 * @property string|null $description
 * @property int $cases_count
 * @property int $threshold
 * @property string $status
 * @property CarbonImmutable $detected_at
 * @property CarbonImmutable|null $acknowledged_at
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $acknowledged_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class EpiAlert extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'epi_alerts';

    protected $fillable = [
        'alert_type', 'diagnostic_code', 'diagnostic_label',
        'region_id', 'severity', 'description',
        'cases_count', 'threshold', 'status',
        'detected_at', 'acknowledged_at', 'resolved_at', 'acknowledged_by',
    ];

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
            'detected_at' => 'immutable_datetime',
            'acknowledged_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
