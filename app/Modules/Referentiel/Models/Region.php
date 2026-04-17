<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\RegionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Region.
 *
 * 1st-level administrative subdivision of a country:
 *   Gabon    : province (9)
 *   Cameroon : région
 *   CI       : district
 *
 * @property int $id
 * @property string $uuid
 * @property int $country_id
 * @property string $code
 * @property string $kind
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $name_local
 * @property int|null $capital_city_id
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Country $country
 * @property-read City|null $capitalCity
 * @property-read Collection<int, City> $cities
 */
class Region extends Model
{
    use HasBilingualName;

    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'country_id',
        'code',
        'kind',
        'name_fr',
        'name_en',
        'name_local',
        'capital_city_id',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function capitalCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'capital_city_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): RegionFactory
    {
        return RegionFactory::new();
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
