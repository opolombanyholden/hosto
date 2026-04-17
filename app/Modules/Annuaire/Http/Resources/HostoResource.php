<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Resources;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Http\Resources\CityResource;
use App\Modules\Referentiel\Http\Resources\SpecialtyResource;
use App\Modules\Referentiel\Http\Resources\StructureTypeResource;
use App\Modules\Referentiel\Models\Service;
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

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,

            // Location
            'city' => new CityResource($this->whenLoaded('city')),
            'address' => $this->address,
            'quarter' => $this->quarter,
            'distance_km' => $this->when(
                isset($this->resource->distance_meters),
                fn () => round($this->resource->distance_meters / 1000, 2),
            ),

            // Contact (always shown)
            'phone' => $this->phone,
            'phone2' => $this->when($isDetail, $this->phone2),
            'whatsapp' => $this->when($isDetail, $this->whatsapp),
            'email' => $this->when($isDetail, $this->email),
            'website' => $this->when($isDetail, $this->website),

            // Classification
            'types' => StructureTypeResource::collection($this->whenLoaded('structureTypes')),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),

            // Details (only on detail view)
            'services' => $this->when($isDetail, function () {
                /** @var Collection<int, Service> $services */
                $services = $this->services;

                return $services->map(fn ($svc) => [
                    'code' => $svc->code,
                    'category' => $svc->category,
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
                ]);
            }),

            // Status
            'is_public' => $this->is_public,
            'is_guard_service' => $this->is_guard_service,
            'is_verified' => $this->is_verified,

            // Detail-only fields
            'description_fr' => $this->when($isDetail, $this->description_fr),
            'description_en' => $this->when($isDetail, $this->description_en),
            'opening_hours' => $this->when($isDetail, $this->opening_hours),
            'emergency_phone' => $this->when($isDetail, $this->emergency_phone),
            'logo_url' => $this->logo_url,
        ];
    }
}
