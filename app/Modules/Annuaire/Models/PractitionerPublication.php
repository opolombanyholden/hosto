<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\TracksActor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $practitioner_id
 * @property string $type
 * @property string|null $title
 * @property string $content
 * @property string|null $video_url
 * @property array<int, string>|null $images
 * @property bool $is_published
 * @property bool $allow_comments
 * @property int $likes_count
 * @property int $comments_count
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Practitioner $practitioner
 * @property-read Collection<int, PublicationLike> $likes
 * @property-read Collection<int, PublicationComment> $comments
 */
class PractitionerPublication extends Model
{
    use HasUuid;
    use SoftDeletes;
    use TracksActor;

    protected $fillable = [
        'practitioner_id', 'type', 'title', 'content', 'video_url', 'images',
        'is_published', 'allow_comments', 'likes_count', 'comments_count', 'published_at',
    ];

    /**
     * @return BelongsTo<Practitioner, $this>
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /**
     * @return HasMany<PublicationLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PublicationLike::class, 'publication_id');
    }

    /**
     * @return HasMany<PublicationComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PublicationComment::class, 'publication_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isLikedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_published' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'immutable_datetime',
        ];
    }
}
