<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\InvitationLink;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private Hosto $hosto;
    private Practitioner $practitioner;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = User::factory()->create(['phone_verified_at' => now()]);
        $this->hosto = Hosto::factory()->create(['is_partner' => true]);
        $this->practitioner = Practitioner::factory()->create([
            'does_teleconsultation' => true,
            'does_home_care' => true,
        ]);
        $this->slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
    }

    public function test_user_can_book_ordinaire_in_hospital(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'reason' => 'Visite contrôle',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'appointment_type' => 'ordinaire',
            'consultation_mode' => 'in_hospital',
        ]);
    }

    public function test_user_cannot_book_telecon_when_not_offered(): void
    {
        $this->practitioner->update(['does_teleconsultation' => false]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'telecon',
        ]);
        $resp->assertSessionHasErrors();
    }

    public function test_user_can_book_home_with_address(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
    }

    public function test_user_can_book_home_with_geolocation(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_lat' => 0.4162,
            'visit_lng' => 9.4673,
            'visit_location_accuracy_m' => 10,
        ]);
        $resp->assertRedirect();
        $apt = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertEqualsWithDelta(0.4162, (float) $apt->visit_lat, 0.0001);
    }

    public function test_user_can_book_for_third_party_with_matched_phone(): void
    {
        $third = User::factory()->create(['name' => 'M. Diop']);
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'M. Diop',
            'third_party_phone' => '06000099',
        ]);
        $resp->assertRedirect();
        $apt = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertSame($third->id, $apt->third_party_user_id);
    }

    public function test_user_can_book_for_third_party_with_unmatched_phone_invites(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Cousin X',
            'third_party_phone' => '06000098',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, InvitationLink::count());
    }

    public function test_user_can_share_medical_record_with_valid_pin(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => '1',
            'medical_pin' => '1234',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, MedicalRecordGrant::where('patient_id', $this->patient->id)->count());
    }

    public function test_user_cannot_share_with_invalid_pin(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => '1',
            'medical_pin' => 'wrong1',
        ]);
        $resp->assertSessionHasErrors();
        $this->assertSame(0, MedicalRecordGrant::count());
    }

    public function test_booking_freezes_patient_identity_snapshot(): void
    {
        $this->patient->forceFill([
            'name' => 'Alice Mbany',
            'email' => 'alice@example.test',
            'phone' => '+24106111222',
            'phone_normalized' => '+24106111222',
            'date_of_birth' => '1990-04-15',
            'gender' => 'female',
            'city_of_residence' => 'Libreville',
            'address_of_residence' => 'BP 1234, Glass',
            'nip' => 'GA-19900415-XYZ',
            'id_document_type' => 'CNI',
            'id_document_number' => '1234567890',
            'blood_group' => 'O+',
        ])->save();

        $resp = $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
        ]);
        $resp->assertRedirect();

        $apt = Appointment::where('patient_id', $this->patient->id)->firstOrFail();

        $this->assertSame('Alice Mbany', $apt->patient_name_snapshot);
        $this->assertSame('alice@example.test', $apt->patient_email_snapshot);
        $this->assertSame('+24106111222', $apt->patient_phone_snapshot);
        $this->assertSame('+24106111222', $apt->patient_phone_normalized_snapshot);
        $this->assertSame('1990-04-15', $apt->patient_dob_snapshot?->toDateString());
        $this->assertSame('female', $apt->patient_gender_snapshot);
        $this->assertSame('Libreville', $apt->patient_city_snapshot);
        $this->assertSame('BP 1234, Glass', $apt->patient_address_snapshot);
        $this->assertSame('GA-19900415-XYZ', $apt->patient_nip_snapshot);
        $this->assertSame('CNI', $apt->patient_id_document_type_snapshot);
        $this->assertSame('1234567890', $apt->patient_id_document_number_snapshot);
        $this->assertSame('O+', $apt->patient_blood_group_snapshot);
    }

    public function test_patient_snapshot_is_immutable_when_user_profile_changes(): void
    {
        $this->patient->forceFill([
            'name' => 'Bob Original',
            'phone' => '+24106333444',
            'city_of_residence' => 'Port-Gentil',
        ])->save();

        $this->actingAs($this->patient)->post('/web/rdv/book-form', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
        ])->assertRedirect();

        $this->patient->forceFill([
            'name' => 'Bob Renamed',
            'phone' => '+24106999999',
            'city_of_residence' => 'Franceville',
        ])->save();

        $apt = Appointment::where('patient_id', $this->patient->id)->firstOrFail()->refresh();

        $this->assertSame('Bob Original', $apt->patient_name_snapshot);
        $this->assertSame('+24106333444', $apt->patient_phone_snapshot);
        $this->assertSame('Port-Gentil', $apt->patient_city_snapshot);
    }
}
