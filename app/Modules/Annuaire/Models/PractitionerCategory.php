<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name_fr
 * @property string|null $name_en
 * @property string|null $description_fr
 * @property string|null $icon_name
 * @property string|null $color_hex
 * @property int|null $parent_category_id
 * @property bool $is_medical
 * @property int $display_order
 * @property bool $is_active
 * @property-read PractitionerCategory|null $parent
 * @property-read Collection<int, PractitionerCategory> $children
 * @property-read Collection<int, Practitioner> $practitioners
 */
class PractitionerCategory extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name_fr', 'name_en', 'description_fr',
        'icon_name', 'color_hex', 'parent_category_id',
        'is_medical', 'display_order', 'is_active',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_category_id')->orderBy('display_order');
    }

    /** @return HasMany<Practitioner, $this> */
    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class, 'practitioner_category_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_medical' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
