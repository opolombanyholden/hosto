<?php

declare(strict_types=1);

namespace App\Modules\Pro\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $consultation_id
 * @property int $practitioner_id
 * @property int $patient_id
 * @property string $status
 * @property string $reference
 * @property CarbonImmutable|null $valid_until
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Consultation $consultation
 * @property-read Practitioner $practitioner
 * @property-read User $patient
 * @property-read Collection<int, PrescriptionItem> $items
 */
class Prescription extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'consultation_id', 'practitioner_id', 'patient_id',
        'status', 'reference', 'valid_until', 'notes',
    ];

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * @return BelongsTo<Practitioner, $this>
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return HasMany<PrescriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('display_order');
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('ORD-%s-%06d', $year, $count);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['valid_until' => 'immutable_datetime'];
    }
}
