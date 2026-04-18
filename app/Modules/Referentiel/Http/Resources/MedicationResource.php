<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Resources;

use App\Modules\Referentiel\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Medication
 */
final class MedicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'dci' => $this->dci,
            'dci_en' => $this->dci_en,
            'name' => $this->name,
            'therapeutic_class' => $this->therapeutic_class,
            'dosage_form' => $this->dosage_form,
            'strength' => $this->strength,
            'prescription_required' => $this->prescription_required,
            'brands' => $this->whenLoaded('brands', fn () => $this->brands->map(fn ($b) => [
                'name' => $b->brand_name,
                'manufacturer' => $b->manufacturer,
                'country' => $b->country_origin,
            ])),
        ];
    }
}
