<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MedicationBrand.
 *
 * A commercial brand name for a generic medication (DCI).
 *
 * @property int $id
 * @property string $uuid
 * @property int $medication_id
 * @property string $brand_name
 * @property string|null $manufacturer
 * @property string|null $country_origin
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Medication $medication
 */
class MedicationBrand extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'medication_brands';

    /** @var list<string> */
    protected $fillable = [
        'medication_id', 'brand_name', 'manufacturer', 'country_origin', 'is_active',
    ];

    /**
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
