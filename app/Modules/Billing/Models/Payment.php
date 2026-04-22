<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $invoice_id
 * @property int $patient_id
 * @property string $reference
 * @property string $method
 * @property string|null $provider
 * @property int $amount
 * @property string $currency_code
 * @property string $status
 * @property string|null $transaction_id
 * @property array<string, mixed>|null $gateway_response
 * @property string|null $notes
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Invoice $invoice
 * @property-read User $patient
 */
class Payment extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'invoice_id', 'patient_id', 'reference', 'method', 'provider',
        'amount', 'currency_code', 'status', 'transaction_id',
        'gateway_response', 'notes', 'completed_at',
    ];

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('PAY-%s-%06d', $year, $count);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
