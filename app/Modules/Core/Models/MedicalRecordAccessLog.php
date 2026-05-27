<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $grant_id
 * @property int $practitioner_user_id
 * @property CarbonImmutable $accessed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<int, string>|null $sections_accessed
 */
class MedicalRecordAccessLog extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'grant_id', 'practitioner_user_id', 'accessed_at',
        'ip_address', 'user_agent', 'sections_accessed',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(MedicalRecordGrant::class);
    }

    public function practitionerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_user_id');
    }

    protected function casts(): array
    {
        return [
            'accessed_at' => 'immutable_datetime',
            'sections_accessed' => 'array',
        ];
    }
}
