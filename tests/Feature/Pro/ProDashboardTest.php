<?php

declare(strict_types=1);

namespace Tests\Feature\Pro;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordAccessLog;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Models\Role;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class ProDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private User $doctor;

    private User $otherDoctor;

    private User $patient;

    private Practitioner $practitioner;

    private Practitioner $otherPractitioner;

    private Hosto $structure;

    protected function setUp(): void
    {
        parent::setUp();

        $doctorRole = Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']);
        $patientRole = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $country = Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        $region = Region::factory()->for($country)->create();
        $city = City::factory()->for($region)->create();
        $this->structure = Hosto::factory()->for($city)->create();

        $this->doctor = User::factory()->create(['pro_validation_status' => 'approved', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $this->doctor->roles()->attach($doctorRole);
        $this->practitioner = Practitioner::factory()->create(['user_id' => $this->doctor->id]);
        $this->practitioner->structures()->attach($this->structure->id, ['is_primary' => true]);

        $this->otherDoctor = User::factory()->create(['pro_validation_status' => 'approved']);
        $this->otherDoctor->roles()->attach($doctorRole);
        $this->otherPractitioner = Practitioner::factory()->create(['user_id' => $this->otherDoctor->id]);

        $this->patient = User::factory()->create(['name' => 'Awa Mengue', 'allergies' => 'Pénicilline']);
        $this->patient->roles()->attach($patientRole);
    }

    public function test_dashboard_shows_real_activity_instead_of_placeholder(): void
    {
        $this->appointment($this->practitioner, $this->patient);
        $this->consultation($this->practitioner, $this->patient);

        $this->actingAs($this->doctor)->get('/pro')
            ->assertOk()
            ->assertDontSee('en cours de construction')
            ->assertSee('Awa Mengue')
            ->assertSee('Suivi tension');
    }

    public function test_dashboard_never_lists_another_practitioners_appointments(): void
    {
        $this->appointment($this->otherPractitioner, $this->patient, ['reason' => 'Motif confidentiel']);

        $this->actingAs($this->doctor)->get('/pro')
            ->assertOk()
            ->assertDontSee('Motif confidentiel');
    }

    public function test_doctor_can_confirm_own_pending_appointment(): void
    {
        $apt = $this->appointment($this->practitioner, $this->patient);

        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/confirmer")->assertRedirect('/pro');

        $apt->refresh();
        $this->assertSame('confirmed', $apt->status);
        $this->assertNotNull($apt->confirmed_at);
    }

    public function test_doctor_cannot_confirm_another_practitioners_appointment(): void
    {
        $apt = $this->appointment($this->otherPractitioner, $this->patient);

        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/confirmer")->assertNotFound();

        $this->assertSame('pending', $apt->refresh()->status);
    }

    public function test_cancelled_appointment_cannot_be_confirmed(): void
    {
        $apt = $this->appointment($this->practitioner, $this->patient, ['status' => 'cancelled_by_patient']);

        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/confirmer")->assertRedirect('/pro');

        $this->assertSame('cancelled_by_patient', $apt->refresh()->status);
    }

    public function test_patients_page_lists_only_linked_patients(): void
    {
        $stranger = User::factory()->create(['name' => 'Personne Inconnue']);
        $this->consultation($this->practitioner, $this->patient);
        $this->consultation($this->otherPractitioner, $stranger);

        $this->actingAs($this->doctor)->get('/pro/patients')
            ->assertOk()
            ->assertSee('Awa Mengue')
            ->assertDontSee('Personne Inconnue');
    }

    public function test_patients_page_can_be_searched_by_name(): void
    {
        $other = User::factory()->create(['name' => 'Paul Essono']);
        $this->consultation($this->practitioner, $this->patient);
        $this->consultation($this->practitioner, $other);

        $this->actingAs($this->doctor)->get('/pro/patients?q=essono')
            ->assertOk()
            ->assertSee('Paul Essono')
            ->assertDontSee('Awa Mengue');
    }

    public function test_cancelled_appointment_alone_does_not_link_a_patient(): void
    {
        $this->appointment($this->practitioner, $this->patient, ['status' => 'cancelled_by_patient']);

        $this->actingAs($this->doctor)->get("/pro/patients/{$this->patient->uuid}")->assertNotFound();
    }

    public function test_unrelated_patient_file_is_not_found(): void
    {
        $this->consultation($this->otherPractitioner, $this->patient);

        $this->actingAs($this->doctor)->get("/pro/patients/{$this->patient->uuid}")->assertNotFound();
    }

    public function test_without_grant_doctor_sees_only_own_consultations(): void
    {
        $this->consultation($this->practitioner, $this->patient, ['motif' => 'Mon suivi']);
        $this->consultation($this->otherPractitioner, $this->patient, ['motif' => 'Secret confrere']);

        $this->actingAs($this->doctor)->get("/pro/patients/{$this->patient->uuid}")
            ->assertOk()
            ->assertSee('Mon suivi')
            ->assertDontSee('Secret confrere')
            ->assertDontSee('Pénicilline');

        $this->assertSame(0, MedicalRecordAccessLog::count());
    }

    public function test_active_grant_opens_shared_history_and_logs_the_access(): void
    {
        $this->consultation($this->practitioner, $this->patient);
        $this->consultation($this->otherPractitioner, $this->patient, [
            'motif' => 'Suivi confrere', 'notes_internes' => 'Note privee du confrere',
        ]);
        $grant = MedicalRecordGrant::create([
            'patient_id' => $this->patient->id, 'practitioner_id' => $this->practitioner->id, 'granted_at' => now(),
        ]);

        $this->actingAs($this->doctor)->get("/pro/patients/{$this->patient->uuid}")
            ->assertOk()
            ->assertSee('Suivi confrere')
            ->assertSee('Pénicilline')
            ->assertDontSee('Note privee du confrere');

        $this->assertSame(1, MedicalRecordAccessLog::where('grant_id', $grant->id)->count());
        $this->assertSame(1, $grant->refresh()->access_count);
    }

    public function test_revoked_grant_no_longer_opens_shared_history(): void
    {
        $this->consultation($this->practitioner, $this->patient);
        $this->consultation($this->otherPractitioner, $this->patient, ['motif' => 'Suivi confrere']);
        MedicalRecordGrant::create([
            'patient_id' => $this->patient->id, 'practitioner_id' => $this->practitioner->id,
            'granted_at' => now()->subDay(), 'revoked_at' => now(),
        ]);

        $this->actingAs($this->doctor)->get("/pro/patients/{$this->patient->uuid}")
            ->assertOk()
            ->assertDontSee('Suivi confrere');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function appointment(Practitioner $practitioner, User $patient, array $attributes = [], ?string $date = null): Appointment
    {
        $slot = TimeSlot::create([
            'practitioner_id' => $practitioner->id, 'hosto_id' => $this->structure->id,
            'date' => $date ?? now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30, 'is_available' => false,
        ]);

        return Appointment::create(array_merge([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id, 'hosto_id' => $this->structure->id,
            'status' => 'pending', 'reason' => 'Suivi tension',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function consultation(Practitioner $practitioner, User $patient, array $attributes = []): Consultation
    {
        return Consultation::create(array_merge([
            'practitioner_id' => $practitioner->id, 'patient_id' => $patient->id, 'hosto_id' => $this->structure->id,
            'status' => 'completed', 'motif' => 'Bilan annuel', 'started_at' => now(), 'completed_at' => now(),
        ], $attributes));
    }
}
