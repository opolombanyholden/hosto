<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\CountryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Country.
 *
 * Referenced by ISO 3166-1 alpha-2 (unique, stable, universal).
 * The API exposes `iso2` as the route key — more user-friendly than UUID.
 *
 * @property int $id
 * @property string $uuid
 * @property string $iso2
 * @property string $iso3
 * @property int|null $iso_numeric
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $name_local
 * @property string|null $phone_prefix
 * @property string|null $currency_code
 * @property string $default_language
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Collection<int, Region> $regions
 */
class Country extends Model
{
    use HasBilingualName;

    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'iso2',
        'iso3',
        'iso_numeric',
        'name_fr',
        'name_en',
        'name_local',
        'phone_prefix',
        'currency_code',
        'default_language',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'iso2';
    }

    /**
     * @return HasMany<Region, $this>
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'iso_numeric' => 'integer',
            'display_order' => 'integer',
        ];
    }
}
