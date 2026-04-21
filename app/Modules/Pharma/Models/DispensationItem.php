<?php

declare(strict_types=1);

namespace App\Modules\Pharma\Models;

use App\Modules\Referentiel\Models\Medication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dispensation_id
 * @property int|null $medication_id
 * @property string $medication_name
 * @property string|null $dosage
 * @property int $quantity
 * @property int $unit_price
 * @property int $total_price
 * @property bool $is_substituted
 * @property string|null $substitution_reason
 * @property int $display_order
 */
class DispensationItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dispensation_id', 'medication_id', 'medication_name', 'dosage',
        'quantity', 'unit_price', 'total_price', 'is_substituted', 'substitution_reason', 'display_order',
    ];

    /** @return BelongsTo<Medication, $this> */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_substituted' => 'boolean'];
    }
}
