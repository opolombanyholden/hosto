<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Resources;

use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Specialty
 */
final class SpecialtyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'name_local' => $this->name_local,
            'parent' => new self($this->whenLoaded('parent')),
            'children' => self::collection($this->whenLoaded('children')),
            'children_count' => $this->whenCounted('children'),
        ];
    }
}
