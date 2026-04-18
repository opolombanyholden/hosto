<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Role.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $name_fr
 * @property string $name_en
 * @property string $environment admin | pro | usager
 * @property string|null $description_fr
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Collection<int, User> $users
 */
class Role extends Model
{
    use HasBilingualName;
    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name_fr',
        'name_en',
        'environment',
        'description_fr',
        'display_order',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}
