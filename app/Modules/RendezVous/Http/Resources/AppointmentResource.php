<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Resources;

use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
final class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'is_teleconsultation' => $this->is_teleconsultation,

            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            'practitioner' => $this->whenLoaded('practitioner', fn () => [
                'uuid' => $this->practitioner->uuid,
                'full_name' => $this->practitioner->full_name,
                'slug' => $this->practitioner->slug,
            ]),
            'structure' => $this->whenLoaded('structure', fn () => [
                'uuid' => $this->structure->uuid,
                'name' => $this->structure->name,
            ]),

            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
