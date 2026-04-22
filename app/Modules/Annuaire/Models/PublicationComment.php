<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $publication_id
 * @property int $user_id
 * @property string $content
 * @property bool $is_approved
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read PractitionerPublication $publication
 * @property-read User $user
 */
class PublicationComment extends Model
{
    protected $fillable = ['publication_id', 'user_id', 'content', 'is_approved'];

    /**
     * @return BelongsTo<PractitionerPublication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(PractitionerPublication::class, 'publication_id');
    }

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
            'is_approved' => 'boolean',
        ];
    }
}
