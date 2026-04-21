<?php

declare(strict_types=1);

namespace App\Modules\Pharma\Models;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Medication;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $hosto_id
 * @property int $medication_id
 * @property int $quantity_in_stock
 * @property int $quantity_min_alert
 * @property int|null $unit_price
 * @property string $currency_code
 * @property bool $is_available
 * @property string|null $expiry_date
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Hosto $pharmacy
 * @property-read Medication $medication
 */
class PharmacyStock extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'hosto_id', 'medication_id', 'quantity_in_stock', 'quantity_min_alert',
        'unit_price', 'currency_code', 'is_available', 'expiry_date',
    ];

    /** @return BelongsTo<Hosto, $this> */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Hosto::class, 'hosto_id');
    }

    /** @return BelongsTo<Medication, $this> */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)->where('quantity_in_stock', '>', 0);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_in_stock', '<=', 'quantity_min_alert');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_available' => 'boolean', 'expiry_date' => 'date'];
    }
}
