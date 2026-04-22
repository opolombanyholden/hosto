<?php

declare(strict_types=1);

namespace App\Modules\AI\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $source_type
 * @property string $file_path
 * @property string $mime_type
 * @property string|null $raw_text
 * @property array<string, mixed>|null $structured_data
 * @property float|null $confidence_score
 * @property string $status
 * @property string|null $error_message
 * @property int|null $processing_time_ms
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 */
class OcrResult extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'ocr_results';

    protected $fillable = [
        'user_id', 'source_type', 'file_path', 'mime_type',
        'raw_text', 'structured_data', 'confidence_score',
        'status', 'error_message', 'processing_time_ms',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
        ];
    }
}
