<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\TracksActor;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Service;
use App\Modules\Referentiel\Models\Specialty;
use App\Modules\Referentiel\Models\StructureType;
use Carbon\CarbonImmutable;
use Database\Factories\Annuaire\HostoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hosto.
 *
 * Central entity of the Annuaire module — a health structure.
 *
 * A single structure can:
 *   - have multiple types      (hospital + lab)
 *   - offer multiple specialties (cardiology + pediatrics)
 *   - provide multiple services with per-structure pricing
 *
 * Geolocation: PostGIS geography(Point, 4326), managed via setCoordinates().
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property int $city_id
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $quarter
 * @property string|null $phone
 * @property string|null $phone2
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $website
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property string|null $twitter_url
 * @property string|null $linkedin_url
 * @property string|null $youtube_url
 * @property string|null $tiktok_url
 * @property array<int, string>|null $featured_sections
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property bool $is_public
 * @property bool $is_guard_service
 * @property bool $is_emergency_service
 * @property bool $is_evacuation_service
 * @property bool $is_home_care_service
 * @property array<string, mixed>|null $opening_hours
 * @property string|null $emergency_phone
 * @property string|null $logo_url
 * @property string|null $cover_image_url
 * @property bool $is_active
 * @property bool $is_verified
 * @property bool $is_partner
 * @property array<int, string>|null $accepted_insurances
 * @property int $likes_count
 * @property CarbonImmutable|null $verified_at
 * @property string|null $verified_by
 * @property string $origin
 * @property int $sync_version
 * @property string $sync_status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property-read City $city
 * @property-read Collection<int, StructureType> $structureTypes
 * @property-read Collection<int, Specialty> $specialties
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, HostoMedia> $media
 * @property-read Collection<int, HostoLike> $likes
 */
class Hosto extends Model
{
    /** @use HasFactory<HostoFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;
    use TracksActor;

    protected $table = 'hostos';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'address',
        'postal_code',
        'quarter',
        'phone',
        'phone2',
        'whatsapp',
        'email',
        'website',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'youtube_url',
        'tiktok_url',
        'featured_sections',
        'description_fr',
        'description_en',
        'is_public',
        'is_guard_service',
        'is_emergency_service',
        'is_evacuation_service',
        'is_home_care_service',
        'opening_hours',
        'emergency_phone',
        'logo_url',
        'cover_image_url',
        'is_active',
        'is_verified',
        'accepted_insurances',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Multiple types per structure (hospital + lab, etc.).
     *
     * @return BelongsToMany<StructureType, $this>
     */
    public function structureTypes(): BelongsToMany
    {
        return $this->belongsToMany(StructureType::class, 'hosto_structure_type')
            ->withPivot('is_primary', 'display_order')
            ->orderByPivot('display_order');
    }

    /**
     * @return BelongsToMany<Specialty, $this>
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'hosto_specialty')
            ->withPivot('display_order')
            ->orderByPivot('display_order');
    }

    /**
     * Services offered with per-structure pricing.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'hosto_service')
            ->withPivot('tarif_min', 'tarif_max', 'currency_code', 'is_available', 'display_order')
            ->orderByPivot('display_order');
    }

    /**
     * All media (profile, cover, gallery).
     *
     * @return HasMany<HostoMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(HostoMedia::class)->orderBy('display_order');
    }

    /**
     * Profile image URL (or null).
     */
    public function profileImageUrl(): ?string
    {
        $media = $this->media->firstWhere('type', 'profile');

        return $media !== null ? $media->url : $this->logo_url;
    }

    /**
     * Cover image URL (or null).
     */
    public function coverImageUrl(): ?string
    {
        $media = $this->media->firstWhere('type', 'cover');

        return $media !== null ? $media->url : $this->cover_image_url;
    }

    /**
     * Gallery images (excluding profile and cover).
     *
     * @return Collection<int, HostoMedia>
     */
    public function galleryImages(): Collection
    {
        return $this->media->where('type', 'gallery')->values();
    }

    /**
     * @return HasMany<HostoLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(HostoLike::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('hostos.is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('hostos.is_verified', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeGuardService(Builder $query): Builder
    {
        return $query->where('hostos.is_guard_service', true);
    }

    /**
     * Get featured sections (admin-defined or auto-detected by type).
     *
     * @return list<string>
     */
    public function getFeaturedSections(): array
    {
        if (! empty($this->featured_sections)) {
            return $this->featured_sections;
        }

        // Auto-detect by structure type
        $typeSlugs = $this->structureTypes->pluck('slug')->toArray();
        $sections = [];

        if (in_array('pharmacie', $typeSlugs, true)) {
            $sections[] = 'catalogue_medicaments';
        }
        if (array_intersect($typeSlugs, ['laboratoire', 'centre-imagerie'])) {
            $sections[] = 'examens';
        }
        if ($this->is_emergency_service) {
            $sections[] = 'urgences';
        }
        if ($this->does_teleconsultation ?? false) {
            $sections[] = 'teleconsultation';
        }

        return $sections;
    }

    /**
     * Available section definitions for admin UI.
     *
     * @return array<string, string>
     */
    public static function availableFeaturedSections(): array
    {
        return [
            'catalogue_medicaments' => 'Catalogue medicaments (pharmacie)',
            'examens' => 'Catalogue des examens (laboratoire)',
            'urgences' => 'Services d\'urgence',
            'teleconsultation' => 'Teleconsultation',
            'prestations' => 'Prestations',
            'soins' => 'Soins',
            'specialites' => 'Specialites',
            'medecins' => 'Medecins',
        ];
    }

    /**
     * Filter by structure type slug.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, string $typeSlug): Builder
    {
        return $query->whereHas('structureTypes', fn (Builder $q) => $q->where('structure_types.slug', $typeSlug));
    }

    /**
     * Filter by specialty code.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithSpecialty(Builder $query, string $specialtyCode): Builder
    {
        return $query->whereHas('specialties', fn (Builder $q) => $q->where('specialties.code', $specialtyCode));
    }

    // ---------------------------------------------------------------
    // Geolocation
    // ---------------------------------------------------------------

    /**
     * Set the PostGIS location from a (latitude, longitude) pair.
     */
    public function setCoordinates(float $latitude, float $longitude): void
    {
        if ($this->getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->getConnection()->statement(
            'UPDATE hostos SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $this->id],
        );
    }

    /**
     * @return array{float, float}|null [latitude, longitude]
     */
    public function coordinates(): ?array
    {
        if ($this->getConnection()->getDriverName() !== 'pgsql') {
            return null;
        }

        $row = $this->getConnection()->selectOne(
            'SELECT ST_Y(location::geometry) AS lat, ST_X(location::geometry) AS lng FROM hostos WHERE id = ?',
            [$this->id],
        );

        if ($row === null || $row->lat === null) {
            return null;
        }

        return [(float) $row->lat, (float) $row->lng];
    }

    protected static function newFactory(): HostoFactory
    {
        return HostoFactory::new();
    }

    // ---------------------------------------------------------------
    // Casts
    // ---------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_guard_service' => 'boolean',
            'is_emergency_service' => 'boolean',
            'is_evacuation_service' => 'boolean',
            'is_home_care_service' => 'boolean',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'immutable_datetime',
            'opening_hours' => 'array',
            'accepted_insurances' => 'array',
            'featured_sections' => 'array',
        ];
    }
}
