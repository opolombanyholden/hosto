<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Resources;

use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StructureType
 */
final class StructureTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'icon' => $this->icon,
            'description_fr' => $this->when($request->routeIs('*.show'), $this->description_fr),
            'description_en' => $this->when($request->routeIs('*.show'), $this->description_en),
        ];
    }
}
