<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $appointment_id
 * @property int $uploaded_by_id
 * @property string $original_name
 * @property string $stored_path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string|null $category
 * @property bool $keep_in_dpe
 */
class AppointmentDocument extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id', 'uploaded_by_id', 'original_name', 'stored_path',
        'mime_type', 'size_bytes', 'category', 'keep_in_dpe',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    protected function casts(): array
    {
        return [
            'keep_in_dpe' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }
}
