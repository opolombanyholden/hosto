<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $scope
 * @property string $name_fr
 * @property string|null $name_en
 * @property string|null $description_fr
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, Role> $roles
 */
class Permission extends Model
{
    use HasUuid;

    protected $fillable = [
        'slug', 'scope', 'name_fr', 'name_en', 'description_fr',
        'is_active', 'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
