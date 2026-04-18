<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Resources;

use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TimeSlot
 */
final class TimeSlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'is_teleconsultation' => $this->is_teleconsultation,
            'fee' => $this->fee,
            'practitioner' => $this->whenLoaded('practitioner', fn () => [
                'uuid' => $this->practitioner->uuid,
                'full_name' => $this->practitioner->full_name,
                'slug' => $this->practitioner->slug,
            ]),
            'structure' => $this->whenLoaded('structure', fn () => [
                'uuid' => $this->structure->uuid,
                'name' => $this->structure->name,
                'slug' => $this->structure->slug,
            ]),
        ];
    }
}
