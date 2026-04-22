<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Models;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pathology statistics per diagnostic code.
 *
 * @property int $id
 * @property Carbon $date
 * @property string $diagnostic_code
 * @property string $diagnostic_label
 * @property int|null $hosto_id
 * @property int|null $region_id
 * @property int $cases_count
 * @property string|null $age_group
 * @property string|null $gender
 */
class PathologyStat extends Model
{
    public $timestamps = false;

    protected $table = 'pathology_stats';

    protected $fillable = [
        'date', 'diagnostic_code', 'diagnostic_label',
        'hosto_id', 'region_id', 'cases_count',
        'age_group', 'gender',
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
