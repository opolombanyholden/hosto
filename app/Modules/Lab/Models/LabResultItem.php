<?php

declare(strict_types=1);

namespace App\Modules\Lab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lab_result_id
 * @property string $test_name
 * @property string|null $test_code
 * @property string $value
 * @property string|null $unit
 * @property string|null $reference_range
 * @property string|null $flag
 * @property string|null $comment
 * @property int $display_order
 */
class LabResultItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lab_result_id', 'test_name', 'test_code', 'value',
        'unit', 'reference_range', 'flag', 'comment', 'display_order',
    ];

    /**
     * @return BelongsTo<LabResult, $this>
     */
    public function labResult(): BelongsTo
    {
        return $this->belongsTo(LabResult::class);
    }
}
