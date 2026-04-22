<?php

declare(strict_types=1);

namespace App\Modules\Connect\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Specialty;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $specialty_id
 * @property string $title
 * @property string $content
 * @property string $category
 * @property bool $is_pinned
 * @property int $replies_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 * @property-read Specialty|null $specialty
 */
class ForumPost extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'specialty_id', 'title', 'content',
        'category', 'is_pinned', 'replies_count',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Specialty, $this> */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }
}
