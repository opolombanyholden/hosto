<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Resources;

use App\Modules\Referentiel\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Region
 */
final class RegionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'kind' => $this->kind,
            'name' => $this->name,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'name_local' => $this->name_local,
            'country' => new CountryResource($this->whenLoaded('country')),
            'capital_city' => new CityResource($this->whenLoaded('capitalCity')),
            'cities_count' => $this->whenCounted('cities'),
        ];
    }
}
