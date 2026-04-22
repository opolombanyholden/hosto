<?php

declare(strict_types=1);

namespace App\Modules\AI\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Region;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $model_name
 * @property string|null $model_version
 * @property string $diagnostic_code
 * @property int|null $region_id
 * @property CarbonImmutable $prediction_date
 * @property int $horizon_days
 * @property int $predicted_cases
 * @property float|null $confidence_lower
 * @property float|null $confidence_upper
 * @property float|null $accuracy_score
 * @property array<string, mixed>|null $features_used
 * @property string|null $interpretation
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Region|null $region
 */
class EpiPrediction extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'epi_predictions';

    protected $fillable = [
        'model_name', 'model_version', 'diagnostic_code', 'region_id',
        'prediction_date', 'horizon_days', 'predicted_cases',
        'confidence_lower', 'confidence_upper', 'accuracy_score',
        'features_used', 'interpretation',
    ];

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'prediction_date' => 'date',
            'features_used' => 'array',
        ];
    }
}
