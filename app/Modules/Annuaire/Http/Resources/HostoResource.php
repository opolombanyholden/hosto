<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Resources;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Http\Resources\CityResource;
use App\Modules\Referentiel\Http\Resources\SpecialtyResource;
use App\Modules\Referentiel\Http\Resources\StructureTypeResource;
use App\Modules\Referentiel\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Hosto
 */
final class HostoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDetail = $request->routeIs('*.show');
        $hours = $this->opening_hours;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,

            // Location
            'city' => new CityResource($this->whenLoaded('city')),
            'address' => $this->address,
            'quarter' => $this->quarter,
            'coordinates' => $this->when(
                method_exists($this->resource, 'coordinates'),
                fn () => $this->formatCoordinates(),
            ),
            'distance_km' => $this->when(
                isset($this->resource->distance_meters),
                fn () => round($this->resource->distance_meters / 1000, 2),
            ),

            // Contact
            'phone' => $this->phone,
            'phone2' => $this->when($isDetail, $this->phone2),
            'whatsapp' => $this->when($isDetail, $this->whatsapp),
            'email' => $this->when($isDetail, $this->email),
            'website' => $this->when($isDetail, $this->website),
            'emergency_phone' => $this->when($isDetail, $this->emergency_phone),

            // Classification
            'types' => StructureTypeResource::collection($this->whenLoaded('structureTypes')),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),

            // Services (detail only, grouped by category)
            'services' => $this->when($isDetail, fn () => $this->formatServices()),

            // Status
            'is_public' => $this->is_public,
            'is_guard_service' => $this->is_guard_service,
            'is_emergency_service' => $this->is_emergency_service,
            'is_evacuation_service' => $this->is_evacuation_service,
            'is_home_care_service' => $this->is_home_care_service,
            'is_verified' => $this->is_verified,
            'is_partner' => $this->resource->is_partner ?? false,
            'accepted_insurances' => $this->resource->accepted_insurances ?? [],
            'likes_count' => $this->resource->likes_count ?? 0,
            'is_open_now' => $this->when($hours !== null, fn () => self::isOpenNow($hours)),

            // Detail-only
            'description_fr' => $this->when($isDetail, $this->description_fr),
            'description_en' => $this->when($isDetail, $this->description_en),
            'opening_hours' => $this->when($isDetail, fn () => self::formatHours($hours)),
            // Media
            'profile_image' => $this->when(
                $this->relationLoaded('media'),
                fn () => $this->resource->profileImageUrl(),
            ),
            'cover_image' => $this->when(
                $isDetail && $this->relationLoaded('media'),
                fn () => $this->resource->coverImageUrl(),
            ),
            'gallery' => $this->when(
                $isDetail && $this->relationLoaded('media'),
                fn () => HostoMediaResource::collection($this->resource->galleryImages()),
            ),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function formatCoordinates(): ?array
    {
        $coords = $this->resource->coordinates();
        if ($coords === null) {
            return null;
        }

        return ['latitude' => $coords[0], 'longitude' => $coords[1]];
    }

    /**
     * Group services by category for cleaner display.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function formatServices(): array
    {
        /** @var Collection<int, Service> $services */
        $services = $this->services;

        $grouped = [];
        foreach ($services as $svc) {
            $grouped[$svc->category][] = [
                'code' => $svc->code,
                'name' => $svc->name,
                'name_fr' => $svc->name_fr,
                'name_en' => $svc->name_en,
                /** @phpstan-ignore-next-line property.notFound */
                'tarif_min' => $svc->pivot->tarif_min,
                /** @phpstan-ignore-next-line property.notFound */
                'tarif_max' => $svc->pivot->tarif_max,
                /** @phpstan-ignore-next-line property.notFound */
                'currency_code' => $svc->pivot->currency_code,
                /** @phpstan-ignore-next-line property.notFound */
                'is_available' => (bool) $svc->pivot->is_available,
            ];
        }

        return $grouped;
    }

    /**
     * Determine if the structure is open right now based on opening_hours.
     *
     * @param  array<string, mixed>|null  $hours
     */
    private static function isOpenNow(?array $hours): bool
    {
        if ($hours === null || $hours === []) {
            return false;
        }

        $dayMap = ['lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim'];
        $now = Carbon::now('Africa/Libreville');
        $dayKey = $dayMap[$now->dayOfWeekIso - 1] ?? null;

        if ($dayKey === null || ! isset($hours[$dayKey])) {
            return false;
        }

        $slot = $hours[$dayKey];
        $open = $slot['open'] ?? null;
        $close = $slot['close'] ?? null;

        if ($open === null || $close === null) {
            return false;
        }

        $currentTime = $now->format('H:i');

        return $currentTime >= $open && $currentTime < $close;
    }

    /**
     * Format opening hours for display with day labels.
     *
     * @param  array<string, mixed>|null  $hours
     * @return list<array<string, mixed>>|null
     */
    private static function formatHours(?array $hours): ?array
    {
        if ($hours === null) {
            return null;
        }

        $labels = [
            'lun' => ['fr' => 'Lundi', 'en' => 'Monday'],
            'mar' => ['fr' => 'Mardi', 'en' => 'Tuesday'],
            'mer' => ['fr' => 'Mercredi', 'en' => 'Wednesday'],
            'jeu' => ['fr' => 'Jeudi', 'en' => 'Thursday'],
            'ven' => ['fr' => 'Vendredi', 'en' => 'Friday'],
            'sam' => ['fr' => 'Samedi', 'en' => 'Saturday'],
            'dim' => ['fr' => 'Dimanche', 'en' => 'Sunday'],
        ];

        $formatted = [];
        foreach ($labels as $key => $dayLabels) {
            $slot = $hours[$key] ?? null;
            $formatted[] = [
                'day' => $key,
                'label_fr' => $dayLabels['fr'],
                'label_en' => $dayLabels['en'],
                'open' => $slot['open'] ?? null,
                'close' => $slot['close'] ?? null,
                'is_closed' => $slot === null,
            ];
        }

        return $formatted;
    }
}
