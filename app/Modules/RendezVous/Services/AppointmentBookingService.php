<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\MedicalRecordGrantService;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AppointmentBookingService
{
    public function __construct(
        private readonly ThirdPartyResolverService $thirdParty,
        private readonly GeocodingService $geocoding,
        private readonly MedicalRecordGrantService $grants,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function book(array $data): Appointment
    {
        /** @var User $patient */
        $patient = $data['patient'];
        $mode = $data['consultation_mode'] ?? 'in_hospital';

        // Derive authoritative slot/practitioner/hosto from the slot itself, ignoring client claims.
        $slot = TimeSlot::findOrFail($data['time_slot_id']);
        $data['practitioner_id'] = $slot->practitioner_id;
        $data['hosto_id'] = $slot->hosto_id;
        $practitioner = Practitioner::findOrFail($slot->practitioner_id);

        $this->validateConsultationMode($mode, $practitioner);
        if ($mode === 'home') {
            $this->validateHomeAddress($data);
        }

        // If share_medical_record requested, validate PIN early so we don't create a phantom appointment.
        if (! empty($data['share_medical_record'])) {
            $pin = $data['medical_pin'] ?? null;
            $stored = $patient->medical_pin;
            if (! $stored || ! Hash::check((string) $pin, $stored)) {
                throw new \DomainException('PIN médical invalide ou non défini');
            }
        }

        // Resolve third party
        $thirdPartyUserId = null;
        $thirdPartyPhoneNormalized = null;
        if (! empty($data['is_for_third_party'])) {
            $rawPhone = $data['third_party_phone'] ?? '';
            $normalized = $this->thirdParty->normalizePhone($rawPhone, 'GA');
            if ($normalized === null) {
                throw new \InvalidArgumentException('Phone tiers invalide');
            }
            $thirdPartyPhoneNormalized = $normalized;
            $match = $this->thirdParty->findUserByPhone($normalized);
            if ($match) {
                $thirdPartyUserId = $match->id;
            }
        }

        $apt = DB::transaction(function () use ($data, $patient, $mode, $thirdPartyUserId) {
            // Lock the slot row + re-check no active booking exists.
            $slot = TimeSlot::lockForUpdate()->findOrFail($data['time_slot_id']);
            $existing = Appointment::where('time_slot_id', $slot->id)
                ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_practitioner'])
                ->lockForUpdate()
                ->exists();
            if ($existing) {
                throw new \DomainException('Ce créneau n\'est plus disponible.');
            }
            return Appointment::create([
                'time_slot_id' => $slot->id,
                'patient_id' => $patient->id,
                'practitioner_id' => $slot->practitioner_id,
                'hosto_id' => $slot->hosto_id,
                'appointment_type' => $data['appointment_type'] ?? 'ordinaire',
                'consultation_mode' => $mode,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'specialty_code' => $data['specialty_code'] ?? null,
                'requested_at' => $data['requested_at'] ?? null,
                'is_for_third_party' => ! empty($data['is_for_third_party']),
                'third_party_name' => $data['third_party_name'] ?? null,
                'third_party_age' => $data['third_party_age'] ?? null,
                'third_party_gender' => $data['third_party_gender'] ?? null,
                'third_party_relation' => $data['third_party_relation'] ?? null,
                'third_party_address' => $data['third_party_address'] ?? null,
                'third_party_city' => $data['third_party_city'] ?? null,
                'third_party_phone' => $data['third_party_phone'] ?? null,
                'third_party_notes' => $data['third_party_notes'] ?? null,
                'third_party_user_id' => $thirdPartyUserId,
                'share_medical_record' => ! empty($data['share_medical_record']),
                'visit_address' => $data['visit_address'] ?? null,
                'visit_lat' => $data['visit_lat'] ?? null,
                'visit_lng' => $data['visit_lng'] ?? null,
                'visit_location_accuracy_m' => $data['visit_location_accuracy_m'] ?? null,
                // Patient identity snapshot — frozen at booking time. Symmetric
                // with third_party_* columns; guarantees no data loss if the
                // User profile is later edited or the account deleted.
                'patient_name_snapshot' => $patient->name,
                'patient_email_snapshot' => $patient->email,
                'patient_phone_snapshot' => $patient->phone,
                'patient_phone_normalized_snapshot' => $patient->phone_normalized,
                'patient_dob_snapshot' => $patient->date_of_birth,
                'patient_gender_snapshot' => $patient->gender,
                'patient_city_snapshot' => $patient->city_of_residence,
                'patient_address_snapshot' => $patient->address_of_residence,
                'patient_nip_snapshot' => $patient->nip,
                'patient_id_document_type_snapshot' => $patient->id_document_type,
                'patient_id_document_number_snapshot' => $patient->id_document_number,
                'patient_blood_group_snapshot' => $patient->blood_group,
            ]);
        });

        // Side effects (outside the transaction)
        if ($mode === 'home' && ($apt->visit_address && $apt->visit_lat === null)) {
            $this->geocoding->dispatchGeocodeJob($apt);
        }

        if (! empty($data['is_for_third_party']) && $thirdPartyUserId === null && $thirdPartyPhoneNormalized) {
            $this->thirdParty->sendInvitation($patient, $thirdPartyPhoneNormalized, $apt);
        }

        if (! empty($data['share_medical_record'])) {
            $this->grants->grant($patient, $apt->practitioner, null, $apt);
        }

        $this->audit->record(AuditLogger::ACTION_CREATE, 'appointment', $apt->uuid, [
            'type' => $apt->appointment_type,
            'mode' => $apt->consultation_mode,
            'third_party' => $apt->is_for_third_party,
            'share_dpe' => $apt->share_medical_record,
        ]);

        return $apt;
    }

    private function validateConsultationMode(string $mode, Practitioner $prac): void
    {
        if ($mode === 'telecon' && ! $prac->does_teleconsultation) {
            throw new \DomainException('Ce praticien ne fait pas de téléconsultation.');
        }
        if ($mode === 'home' && ! $prac->does_home_care) {
            throw new \DomainException('Ce praticien ne fait pas de visite à domicile.');
        }
        if (! in_array($mode, ['in_hospital', 'home', 'telecon'], true)) {
            throw new \InvalidArgumentException("Mode invalide: {$mode}");
        }
    }

    private function validateHomeAddress(array $data): void
    {
        $hasAddress = ! empty($data['visit_address']);
        $hasCoords = isset($data['visit_lat'], $data['visit_lng']);
        if (! $hasAddress && ! $hasCoords) {
            throw new \InvalidArgumentException('Adresse ou coordonnées requises pour une visite à domicile');
        }
    }
}
