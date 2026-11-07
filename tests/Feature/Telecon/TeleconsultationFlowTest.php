<?php

declare(strict_types=1);

namespace Tests\Feature\Telecon;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\Telecon\Models\Teleconsultation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class TeleconsultationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $doctor;

    private User $patient;

    private User $outsider;

    private Practitioner $practitioner;

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

        $verified = ['email_verified_at' => now(), 'phone_verified_at' => now()];
        $this->doctor = User::factory()->create($verified + ['pro_validation_status' => 'approved']);
        $this->doctor->roles()->attach($doctorRole);
        $this->practitioner = Practitioner::factory()->create(['user_id' => $this->doctor->id]);

        $this->patient = User::factory()->create($verified + ['email' => 'awa.patient@example.org']);
        $this->patient->roles()->attach($patientRole);

        $this->outsider = User::factory()->create($verified);
        $this->outsider->roles()->attach($patientRole);
    }

    public function test_doctor_starts_session_from_confirmed_telecon_appointment(): void
    {
        config(['hosto.telecon.jitsi_domain' => 'visio.example.org']);
        $apt = $this->appointment();

        $response = $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/teleconsultation");

        $tc = Teleconsultation::where('appointment_id', $apt->id)->firstOrFail();
        $response->assertRedirect("/teleconsultation/{$tc->uuid}");
        $this->assertSame('visio.example.org', $tc->jitsi_domain);
        $this->assertSame($this->patient->id, $tc->patient_id);
        $this->assertStringStartsWith('hosto-', $tc->room_name);
    }

    public function test_starting_twice_reuses_the_same_session(): void
    {
        $apt = $this->appointment();

        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/teleconsultation");
        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$apt->uuid}/teleconsultation");

        $this->assertSame(1, Teleconsultation::where('appointment_id', $apt->id)->count());
    }

    public function test_session_cannot_start_for_pending_or_in_person_appointment(): void
    {
        $pending = $this->appointment(['status' => 'pending']);
        $inPerson = $this->appointment(['consultation_mode' => 'in_person']);

        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$pending->uuid}/teleconsultation")->assertRedirect('/pro');
        $this->actingAs($this->doctor)->post("/pro/rendez-vous/{$inPerson->uuid}/teleconsultation")->assertRedirect('/pro');

        $this->assertSame(0, Teleconsultation::count());
    }

    public function test_another_doctor_cannot_start_the_session(): void
    {
        $other = User::factory()->create(['phone_verified_at' => now()]);
        $other->roles()->attach(Role::where('slug', 'doctor')->first());
        Practitioner::factory()->create(['user_id' => $other->id]);
        $apt = $this->appointment();

        $this->actingAs($other)->post("/pro/rendez-vous/{$apt->uuid}/teleconsultation")->assertNotFound();

        $this->assertSame(0, Teleconsultation::count());
    }

    public function test_only_participants_can_open_the_room(): void
    {
        $tc = $this->videoSession();

        $this->actingAs($this->patient)->get("/teleconsultation/{$tc->uuid}")->assertOk();
        $this->actingAs($this->doctor)->get("/teleconsultation/{$tc->uuid}")->assertOk();
        $this->actingAs($this->outsider)->get("/teleconsultation/{$tc->uuid}")->assertNotFound();
    }

    public function test_room_makes_no_end_to_end_claim_and_does_not_send_email_to_video_provider(): void
    {
        $tc = $this->videoSession();

        $this->actingAs($this->patient)->get("/teleconsultation/{$tc->uuid}")
            ->assertOk()
            ->assertDontSee('bout en bout')
            ->assertDontSee('awa.patient@example.org')
            ->assertSee('https://visio.example.org/external_api.js', false);
    }

    public function test_finished_session_cannot_be_reopened(): void
    {
        $tc = $this->videoSession(attributes: ['status' => 'completed']);

        $this->actingAs($this->patient)->get("/teleconsultation/{$tc->uuid}")->assertNotFound();
    }

    public function test_join_marks_the_right_participant(): void
    {
        $tc = $this->videoSession();

        $this->actingAs($this->patient)->post("/web/telecon/{$tc->uuid}/join")->assertOk();
        $tc->refresh();
        $this->assertTrue((bool) $tc->patient_joined);
        $this->assertFalse((bool) $tc->practitioner_joined);
        $this->assertSame('in_progress', $tc->status);

        $this->actingAs($this->doctor)->post("/web/telecon/{$tc->uuid}/join")->assertOk();
        $this->assertTrue((bool) $tc->refresh()->practitioner_joined);
    }

    public function test_outsider_cannot_join_or_end_a_session(): void
    {
        $tc = $this->videoSession(attributes: ['status' => 'in_progress', 'started_at' => now()]);

        $this->actingAs($this->outsider)->post("/web/telecon/{$tc->uuid}/join")->assertNotFound();
        $this->actingAs($this->outsider)->post("/web/telecon/{$tc->uuid}/end")->assertNotFound();

        $tc->refresh();
        $this->assertSame('in_progress', $tc->status);
        $this->assertFalse((bool) $tc->practitioner_joined);
    }

    public function test_patient_leaving_does_not_end_the_session(): void
    {
        $tc = $this->videoSession(attributes: ['status' => 'in_progress', 'started_at' => now()]);

        $this->actingAs($this->patient)->post("/web/telecon/{$tc->uuid}/end")->assertOk();

        $this->assertSame('in_progress', $tc->refresh()->status);
    }

    public function test_doctor_ending_completes_session_and_appointment(): void
    {
        $apt = $this->appointment();
        $tc = $this->videoSession($apt, ['status' => 'in_progress', 'started_at' => now()->subMinutes(12)]);

        $this->actingAs($this->doctor)->post("/web/telecon/{$tc->uuid}/end")->assertOk();

        $tc->refresh();
        $this->assertSame('completed', $tc->status);
        $this->assertNotNull($tc->ended_at);
        $this->assertSame(12, $tc->actual_duration_minutes);
        $apt->refresh();
        $this->assertSame('completed', $apt->status);
        $this->assertNotNull($apt->completed_at);
    }

    public function test_patient_sees_join_link_once_session_exists(): void
    {
        $tc = $this->videoSession();

        $this->actingAs($this->patient)->get('/compte/rendez-vous')
            ->assertOk()
            ->assertSee("/teleconsultation/{$tc->uuid}", false);
    }

    private function appointment(array $attributes = []): Appointment
    {
        $slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->structure->id,
            'date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '10:30',
            'duration_minutes' => 30, 'is_available' => false, 'is_teleconsultation' => true,
        ]);

        return Appointment::create(array_merge([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->structure->id,
            'status' => 'confirmed', 'consultation_mode' => 'telecon', 'reason' => 'Suivi',
        ], $attributes));
    }

    private function videoSession(?Appointment $apt = null, array $attributes = []): Teleconsultation
    {
        $apt ??= $this->appointment();

        return Teleconsultation::create(array_merge([
            'appointment_id' => $apt->id, 'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id, 'room_name' => Teleconsultation::generateRoomName(),
            'jitsi_domain' => 'visio.example.org', 'status' => 'scheduled', 'scheduled_at' => now(),
        ], $attributes));
    }
}
