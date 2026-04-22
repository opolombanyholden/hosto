<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $invoice_id
 * @property int $insurance_card_id
 * @property int $patient_id
 * @property int $hosto_id
 * @property string $reference
 * @property string $status
 * @property int $claimed_amount
 * @property int|null $approved_amount
 * @property int|null $paid_amount
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Invoice $invoice
 * @property-read InsuranceCard $insuranceCard
 * @property-read User $patient
 * @property-read Hosto $hosto
 */
class InsuranceClaim extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'invoice_id', 'insurance_card_id', 'patient_id', 'hosto_id',
        'reference', 'status', 'claimed_amount', 'approved_amount',
        'paid_amount', 'rejection_reason', 'submitted_at', 'reviewed_at', 'paid_at',
    ];

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<InsuranceCard, $this> */
    public function insuranceCard(): BelongsTo
    {
        return $this->belongsTo(InsuranceCard::class);
    }

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

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('CLM-%s-%06d', $year, $count);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }
}
