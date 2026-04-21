<?php

declare(strict_types=1);

namespace App\Modules\Pro\Models;

use App\Modules\Referentiel\Models\Medication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prescription_id
 * @property int|null $medication_id
 * @property string $medication_name
 * @property string|null $dosage
 * @property string|null $posology
 * @property string|null $duration
 * @property int|null $quantity
 * @property string|null $instructions
 * @property int $display_order
 * @property-read Prescription $prescription
 * @property-read Medication|null $medication
 */
class PrescriptionItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'prescription_id', 'medication_id', 'medication_name',
        'dosage', 'posology', 'duration', 'quantity', 'instructions', 'display_order',
    ];

    /**
     * @return BelongsTo<Prescription, $this>
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
