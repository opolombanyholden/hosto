<?php

declare(strict_types=1);

namespace App\Modules\Pharma\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Pro\Models\Prescription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $prescription_id
 * @property int $hosto_id
 * @property int $patient_id
 * @property int|null $dispensed_by_id
 * @property string $reference
 * @property string $status
 * @property int $total_amount
 * @property string|null $payment_method
 * @property bool $is_paid
 * @property string|null $delivery_code
 * @property string|null $notes
 * @property CarbonImmutable|null $dispensed_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Prescription|null $prescription
 * @property-read Hosto $pharmacy
 * @property-read User $patient
 * @property-read Collection<int, DispensationItem> $items
 */
class Dispensation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'prescription_id', 'hosto_id', 'patient_id', 'dispensed_by_id',
        'reference', 'status', 'total_amount', 'payment_method', 'is_paid',
        'delivery_code', 'notes', 'dispensed_at', 'delivered_at',
    ];

    /** @return BelongsTo<Prescription, $this> */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /** @return BelongsTo<Hosto, $this> */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Hosto::class, 'hosto_id');
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return HasMany<DispensationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(DispensationItem::class)->orderBy('display_order');
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('DISP-%s-%06d', $year, $count);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'dispensed_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }
}
