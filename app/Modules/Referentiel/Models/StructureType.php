<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\StructureTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StructureType.
 *
 * Categorizes health structures: hospital, clinic, pharmacy, lab, etc.
 * Referenced by slug for URL-friendliness: /annuaire?type=pharmacie
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $icon
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 */
class StructureType extends Model
{
    use HasBilingualName;

    /** @use HasFactory<StructureTypeFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name_fr',
        'name_en',
        'icon',
        'description_fr',
        'description_en',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): StructureTypeFactory
    {
        return StructureTypeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
