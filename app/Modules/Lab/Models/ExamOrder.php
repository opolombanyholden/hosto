<?php

declare(strict_types=1);

namespace App\Modules\Lab\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $hosto_id
 * @property array<int, array<string, mixed>> $exam_items
 * @property int|null $total_amount
 * @property string $currency_code
 * @property string|null $payment_method
 * @property string $payment_status
 * @property string $status
 * @property string|null $notes
 * @property string|null $prescription_file_path
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class ExamOrder extends Model
{
    use HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_ONLINE = 'online';
    public const PAYMENT_ON_SITE = 'on_site';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'hosto_id', 'exam_items', 'total_amount', 'currency_code',
        'payment_method', 'payment_status', 'status', 'notes',
        'prescription_file_path', 'rejection_reason',
        'accepted_at', 'paid_at', 'completed_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Hosto, $this> */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'exam_items' => 'array',
            'accepted_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
