<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Pharma\Models\Dispensation;
use App\Modules\Pro\Models\Consultation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $patient_id
 * @property int $hosto_id
 * @property int|null $consultation_id
 * @property int|null $dispensation_id
 * @property string $reference
 * @property string $status
 * @property int $subtotal
 * @property int $discount
 * @property int $insurance_amount
 * @property int $patient_amount
 * @property int $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $patient
 * @property-read Hosto $hosto
 * @property-read Consultation|null $consultation
 * @property-read Dispensation|null $dispensation
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, InsuranceClaim> $insuranceClaims
 */
class Invoice extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'hosto_id', 'consultation_id', 'dispensation_id',
        'reference', 'status', 'subtotal', 'discount', 'insurance_amount',
        'patient_amount', 'total_amount', 'currency_code', 'notes',
        'issued_at', 'paid_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<Hosto, $this> */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /** @return BelongsTo<Dispensation, $this> */
    public function dispensation(): BelongsTo
    {
        return $this->belongsTo(Dispensation::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('display_order');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<InsuranceClaim, $this> */
    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('FAC-%s-%06d', $year, $count);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }
}
