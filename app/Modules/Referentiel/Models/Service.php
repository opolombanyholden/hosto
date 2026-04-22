<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Service.
 *
 * A medical service, treatment, or exam that a health structure can offer.
 *
 * Categories:
 *   - prestation : consultation, hospitalisation, chirurgie, urgence
 *   - soin       : injection, pansement, suture, perfusion
 *   - examen     : radiographie, échographie, bilan sanguin
 *
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $category
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Pivot|null $pivot
 */
class Service extends Model
{
    use HasBilingualName;

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'category',
        'name_fr',
        'name_en',
        'description_fr',
        'description_en',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
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
