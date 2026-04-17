<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\CityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * City.
 *
 * Geolocated by a PostGIS geography(Point, 4326) column managed via
 * dedicated accessors — Eloquent does not natively understand PostGIS.
 *
 * @property int $id
 * @property string $uuid
 * @property int $region_id
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $name_local
 * @property bool $is_capital
 * @property int|null $population
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Region $region
 */
class City extends Model
{
    use HasBilingualName;

    /** @use HasFactory<CityFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'region_id',
        'name_fr',
        'name_en',
        'name_local',
        'is_capital',
        'population',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
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
     * Set the PostGIS location from a (latitude, longitude) pair.
     */
    public function setCoordinates(float $latitude, float $longitude): void
    {
        // Only applicable on PostgreSQL with PostGIS.
        if ($this->getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->getConnection()->statement(
            'UPDATE cities SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $this->id],
        );
    }

    /**
     * Return the location as [latitude, longitude] or null.
     *
     * @return array{float, float}|null
     */
    public function coordinates(): ?array
    {
        if ($this->getConnection()->getDriverName() !== 'pgsql') {
            return null;
        }

        $row = $this->getConnection()->selectOne(
            'SELECT ST_Y(location::geometry) AS lat, ST_X(location::geometry) AS lng FROM cities WHERE id = ?',
            [$this->id],
        );

        if ($row === null || $row->lat === null) {
            return null;
        }

        return [(float) $row->lat, (float) $row->lng];
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_capital' => 'boolean',
            'is_active' => 'boolean',
            'population' => 'integer',
            'display_order' => 'integer',
        ];
    }
}
