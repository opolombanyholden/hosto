<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $phone
 * @property string|null $relation
 * @property bool $can_access_medical_record
 * @property int $priority
 * @property-read User $user
 */
class EmergencyContact extends Model
{
    protected $fillable = [
        'user_id', 'name', 'phone', 'relation',
        'can_access_medical_record', 'priority',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_access_medical_record' => 'boolean',
            'priority' => 'integer',
        ];
    }
}
