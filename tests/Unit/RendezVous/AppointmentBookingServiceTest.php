<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\RendezVous\Services\AppointmentBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AppointmentBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentBookingService $svc;
    private User $patient;
    private Hosto $hosto;
    private Practitioner $practitioner;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AppointmentBookingService::class);
        $this->patient = User::factory()->create();
        $this->hosto = Hosto::factory()->create();
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

    public function test_book_creates_appointment_with_defaults(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
        ]);
        $this->assertSame('ordinaire', $apt->appointment_type);
        $this->assertSame('in_hospital', $apt->consultation_mode);
        $this->assertFalse($apt->share_medical_record);
    }

    public function test_book_urgence_type(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'appointment_type' => 'urgence',
        ]);
        $this->assertTrue($apt->isUrgent());
    }

    public function test_book_telecon_refuses_when_practitioner_doesnt_offer(): void
    {
        $this->practitioner->update(['does_teleconsultation' => false]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'telecon',
        ]);
    }

    public function test_book_home_refuses_when_practitioner_doesnt_offer(): void
    {
        $this->practitioner->update(['does_home_care' => false]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'X',
        ]);
    }

    public function test_book_home_requires_address_or_coords(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
        ]);
    }

    public function test_book_third_party_with_matched_phone_sets_user_id(): void
    {
        $third = User::factory()->create();
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();

        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Ami',
            'third_party_phone' => '+24106000099',
        ]);
        $this->assertSame($third->id, $apt->third_party_user_id);
    }

    public function test_book_third_party_with_unmatched_phone_creates_invitation(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Cousin',
            'third_party_phone' => '+24199999998',
        ]);
        $this->assertNull($apt->third_party_user_id);
        $this->assertDatabaseHas('invitation_links', [
            'phone_normalized' => '+24199999998',
            'context' => 'appointment_third_party',
            'context_id' => $apt->id,
        ]);
    }

    public function test_book_with_share_medical_record_and_valid_pin_creates_grant(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => true,
            'medical_pin' => '1234',
        ]);
        $this->assertTrue($apt->share_medical_record);
        $this->assertSame(1, MedicalRecordGrant::where('patient_id', $this->patient->id)->count());
    }

    public function test_book_with_share_medical_record_and_invalid_pin_aborts(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => true,
            'medical_pin' => 'wrong',
        ]);
    }

    public function test_book_home_address_dispatches_geocode_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob::class);
    }
}
