<?php

declare(strict_types=1);

namespace Tests\Feature\Pro;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class ConsultationWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $doctor;

    private User $patient;

    private Practitioner $practitioner;

    private Hosto $structure;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']);
        Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $country = Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        $region = Region::factory()->for($country)->create();
        $city = City::factory()->for($region)->create();

        $this->structure = Hosto::factory()->for($city)->create();
        $this->doctor = User::factory()->create();
        $this->doctor->roles()->attach(Role::where('slug', 'doctor')->first());

        $this->practitioner = Practitioner::factory()->create(['user_id' => $this->doctor->id]);
        $this->practitioner->structures()->attach($this->structure->id, ['is_primary' => true]);

        $this->patient = User::factory()->create();
        $this->patient->roles()->attach(Role::where('slug', 'patient')->first());
    }

    public function test_doctor_can_create_consultation(): void
    {
        $this->actingAs($this->doctor)
            ->post('/pro/consultations', [
                'patient_email' => $this->patient->email,
                'hosto_id' => $this->structure->id,
                'motif' => 'Douleur thoracique',
                'diagnostic' => 'Angine pectorine stable',
                'diagnostic_code' => 'I20.9',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'motif' => 'Douleur thoracique',
            'diagnostic_code' => 'I20.9',
        ]);
    }

    public function test_doctor_can_add_exam_request(): void
    {
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id,
            'hosto_id' => $this->structure->id,
        ]);

        $this->actingAs($this->doctor)
            ->post("/pro/consultations/{$consultation->uuid}/examen", [
                'exam_type' => 'Bilan sanguin complet',
                'urgency' => 'normal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_requests', [
            'consultation_id' => $consultation->id,
            'exam_type' => 'Bilan sanguin complet',
        ]);
    }

    public function test_doctor_can_add_care_act(): void
    {
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id,
            'hosto_id' => $this->structure->id,
        ]);

        $this->actingAs($this->doctor)
            ->post("/pro/consultations/{$consultation->uuid}/soin", [
                'care_type' => 'injection',
                'description' => 'Injection intramusculaire de Diclofenac',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('care_acts', [
            'consultation_id' => $consultation->id,
            'care_type' => 'injection',
        ]);
    }

    public function test_doctor_can_add_treatment(): void
    {
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id,
            'hosto_id' => $this->structure->id,
        ]);

        $this->actingAs($this->doctor)
            ->post("/pro/consultations/{$consultation->uuid}/traitement", [
                'type' => 'diet',
                'description' => 'Regime pauvre en sel',
                'duration' => '3 mois',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'consultation_id' => $consultation->id,
            'type' => 'diet',
        ]);
    }

    public function test_doctor_can_add_prescription(): void
    {
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id,
            'hosto_id' => $this->structure->id,
        ]);

        $this->actingAs($this->doctor)
            ->post("/pro/consultations/{$consultation->uuid}/ordonnance", [
                'items' => [
                    ['medication_name' => 'Paracetamol 500mg', 'posology' => '1cp x 3/jour', 'duration' => '5 jours'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('prescriptions', [
            'consultation_id' => $consultation->id,
            'patient_id' => $this->patient->id,
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'medication_name' => 'Paracetamol 500mg',
        ]);
    }

    public function test_patient_can_view_own_consultation(): void
    {
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id,
            'hosto_id' => $this->structure->id,
            'motif' => 'Test visibility',
        ]);

        $this->actingAs($this->patient)
            ->get("/compte/dossier-medical/{$consultation->uuid}")
            ->assertOk()
            ->assertSee('Test visibility');
    }

    public function test_patient_cannot_view_other_patients_consultation(): void
    {
        $otherPatient = User::factory()->create();
        $consultation = Consultation::factory()->create([
            'practitioner_id' => $this->practitioner->id,
            'patient_id' => $otherPatient->id,
            'hosto_id' => $this->structure->id,
        ]);

        $this->actingAs($this->patient)
            ->get("/compte/dossier-medical/{$consultation->uuid}")
            ->assertStatus(404);
    }
}
