<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_for_third_party' => $this->boolean('is_for_third_party'),
            'share_medical_record' => $this->boolean('share_medical_record'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'practitioner_id' => 'required|integer|exists:practitioners,id',
            'hosto_id' => 'required|integer|exists:hostos,id',
            'appointment_type' => 'nullable|in:ordinaire,urgence,grossesse,natalite,chronique',
            'consultation_mode' => 'nullable|in:in_hospital,home,telecon',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'specialty_code' => 'nullable|string|max:20',
            'requested_at' => 'nullable|date|after:now',
            'is_for_third_party' => 'sometimes|boolean',
            'third_party_name' => 'required_if:is_for_third_party,true|nullable|string|max:255',
            'third_party_phone' => 'required_if:is_for_third_party,true|nullable|string|max:30',
            'third_party_age' => 'nullable|integer|min:0|max:120',
            'third_party_gender' => 'nullable|in:male,female,other',
            'third_party_relation' => 'nullable|string|max:30',
            'third_party_address' => 'nullable|string|max:255',
            'third_party_city' => 'nullable|string|max:120',
            'third_party_notes' => 'nullable|string|max:1000',
            'share_medical_record' => 'sometimes|boolean',
            'medical_pin' => 'required_if:share_medical_record,true|nullable|string|digits_between:4,6',
            'visit_address' => 'nullable|string|max:500',
            'visit_lat' => 'nullable|numeric|between:-90,90',
            'visit_lng' => 'nullable|numeric|between:-180,180',
            'visit_location_accuracy_m' => 'nullable|integer|min:0|max:10000',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|max:10240|mimetypes:application/pdf,image/jpeg,image/png,image/heic,image/heif',
        ];
    }

    public function messages(): array
    {
        return [
            'documents.max' => 'Maximum 5 documents par RDV.',
            'documents.*.max' => 'Chaque document doit faire moins de 10 MB.',
            'medical_pin.required_if' => 'PIN médical requis pour partager le dossier.',
        ];
    }
}
