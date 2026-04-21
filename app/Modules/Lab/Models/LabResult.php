<?php

declare(strict_types=1);

namespace App\Modules\Lab\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Pro\Models\ExamRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $exam_request_id
 * @property int $hosto_id
 * @property int $patient_id
 * @property int|null $performed_by_id
 * @property string $reference
 * @property string $status
 * @property CarbonImmutable|null $sample_collected_at
 * @property CarbonImmutable|null $analysis_started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $validated_at
 * @property string|null $validated_by
 * @property string|null $conclusion
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read ExamRequest $examRequest
 * @property-read Hosto $laboratory
 * @property-read User $patient
 * @property-read Collection<int, LabResultItem> $items
 */
class LabResult extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'exam_request_id', 'hosto_id', 'patient_id', 'performed_by_id',
        'reference', 'status', 'sample_collected_at', 'analysis_started_at',
        'completed_at', 'validated_at', 'validated_by', 'conclusion', 'notes',
    ];

    /**
     * @return BelongsTo<ExamRequest, $this>
     */
    public function examRequest(): BelongsTo
    {
        return $this->belongsTo(ExamRequest::class);
    }

    /**
     * @return BelongsTo<Hosto, $this>
     */
    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Hosto::class, 'hosto_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return HasMany<LabResultItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LabResultItem::class)->orderBy('display_order');
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('LAB-%s-%06d', $year, $count);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_collected_at' => 'immutable_datetime',
            'analysis_started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
        ];
    }
}
