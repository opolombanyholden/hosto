<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Resources;

use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Referentiel\Http\Resources\SpecialtyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Practitioner
 */
final class PractitionerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDetail = $request->routeIs('*.show');

        return [
            'uuid' => $this->uuid,
            'full_name' => $this->full_name,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'slug' => $this->slug,
            'gender' => $this->gender,
            'practitioner_type' => $this->practitioner_type,
            'profile_image_url' => $this->profile_image_url,

            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'structures' => HostoResource::collection($this->whenLoaded('structures')),

            'phone' => $this->when($isDetail, $this->phone),
            'email' => $this->when($isDetail, $this->email),
            'bio_fr' => $this->when($isDetail, $this->bio_fr),
            'bio_en' => $this->when($isDetail, $this->bio_en),
            'languages' => $this->when($isDetail, $this->languages),

            'consultation_fee_min' => $this->consultation_fee_min,
            'consultation_fee_max' => $this->consultation_fee_max,
            'accepts_new_patients' => $this->accepts_new_patients,
            'does_teleconsultation' => $this->does_teleconsultation,
            'is_verified' => $this->is_verified,
        ];
    }
}
