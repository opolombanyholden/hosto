<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Resources;

use App\Modules\Referentiel\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
final class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'name' => $this->name,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'name_local' => $this->name_local,
            'phone_prefix' => $this->phone_prefix,
            'currency_code' => $this->currency_code,
            'regions_count' => $this->whenCounted('regions'),
        ];
    }
}
