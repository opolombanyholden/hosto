<?php

declare(strict_types=1);

namespace Tests\Feature\Lab;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Lab\Models\LabResult;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\ExamRequest;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LabWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $patient;

    private User $doctor;

    private User $tech;

    private User $otherTech;

    private Hosto $lab;

    private Consultation $consultation;

    private ExamRequest $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $type = StructureType::firstOrCreate(['slug' => 'laboratoire'], ['name_fr' => 'Laboratoire', 'name_en' => 'Laboratory']);
        $role = Role::firstOrCreate(['slug' => 'lab_tech'], ['name_fr' => 'Laborantin', 'name_en' => 'Lab tech', 'environment' => 'pro']);
        $doctorRole = Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']);
        $patientRole = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $this->lab = Hosto::factory()->create();
        $otherLab = Hosto::factory()->create();
        $this->lab->structureTypes()->attach($type->id);
        $otherLab->structureTypes()->attach($type->id);

        $this->tech = User::factory()->create();
        UserRoleAssignment::create(['user_id' => $this->tech->id, 'role_id' => $role->id, 'scope_type' => Hosto::class, 'scope_id' => $this->lab->id]);
        $this->otherTech = User::factory()->create();
        UserRoleAssignment::create(['user_id' => $this->otherTech->id, 'role_id' => $role->id, 'scope_type' => Hosto::class, 'scope_id' => $otherLab->id]);

        $this->patient = User::factory()->create(['medical_pin' => Hash::make('1234'), 'medical_pin_set_at' => now()]);
        $this->patient->roles()->attach($patientRole);

        $this->doctor = User::factory()->create();
        $this->doctor->roles()->attach($doctorRole);
        $practitioner = Practitioner::factory()->create(['user_id' => $this->doctor->id]);

        $this->consultation = Consultation::create([
            'practitioner_id' => $practitioner->id, 'patient_id' => $this->patient->id, 'hosto_id' => $this->lab->id,
            'status' => 'completed', 'motif' => 'Bilan',
        ]);
        $this->exam = ExamRequest::create([
            'consultation_id' => $this->consultation->id, 'practitioner_id' => $practitioner->id,
            'patient_id' => $this->patient->id, 'exam_type' => 'Glycemie', 'clinical_info' => 'Soif intense',
        ]);
    }

    public function test_patient_sends_exam_to_a_laboratory(): void
    {
        $result = $this->send();

        $this->assertSame('received', $result->status);
        $this->assertSame($this->lab->id, $result->hosto_id);
        $this->assertSame('scheduled', $this->exam->refresh()->status);
    }

    public function test_exam_cannot_go_to_a_non_laboratory_or_twice(): void
    {
        $this->asPatient()->post("/compte/soins/examens/{$this->exam->uuid}/laboratoire", ['laboratory_uuid' => Hosto::factory()->create()->uuid])
            ->assertSessionHasErrors('laboratory_uuid');
        $this->assertSame(0, LabResult::count());

        $this->send();
        $this->asPatient()->post("/compte/soins/examens/{$this->exam->uuid}/laboratoire", ['laboratory_uuid' => $this->lab->uuid]);
        $this->assertSame(1, LabResult::where('exam_request_id', $this->exam->id)->count());
    }

    public function test_tech_sees_only_their_laboratory(): void
    {
        $result = $this->send();

        $this->actingAs($this->tech)->get('/pro/laboratoire')->assertOk()->assertSee($result->reference);
        $this->actingAs($this->otherTech)->get('/pro/laboratoire')->assertOk()->assertDontSee($result->reference);
        $this->actingAs($this->otherTech)->get("/pro/laboratoire/analyses/{$result->uuid}")->assertNotFound();
        $this->actingAs($this->doctor)->get('/pro/laboratoire')->assertForbidden();
    }

    public function test_results_cannot_be_entered_before_the_sample(): void
    {
        $result = $this->send();

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/resultats", [
            'items' => [['test_name' => 'Glycemie', 'value' => '1']],
        ]);

        $this->assertSame('received', $result->refresh()->status);
        $this->assertSame(0, $result->items()->count());
    }

    public function test_unvalidated_result_is_hidden_from_patient_and_doctor(): void
    {
        $result = $this->send();
        $this->complete($result);

        $this->assertSame('completed', $result->refresh()->status);
        $this->asPatient()->get('/compte/soins')->assertOk()->assertDontSee('1.42');
        $this->actingAs($this->doctor)->get("/pro/consultations/{$this->consultation->uuid}")->assertOk()->assertDontSee('1.42');
    }

    public function test_validation_transmits_to_patient_and_prescriber(): void
    {
        $result = $this->send();
        $this->complete($result);

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/valider")
            ->assertRedirect("/pro/laboratoire/analyses/{$result->uuid}");

        $result->refresh();
        $this->assertSame('transmitted', $result->status);
        $this->assertNotNull($result->validated_at);
        $this->assertSame($this->tech->uuid, $result->validated_by);
        $this->assertSame('completed', $this->exam->refresh()->status);

        $this->asPatient()->get('/compte/soins')->assertOk()->assertSee('1.42')->assertSee('Glycemie elevee a controler');
        $this->actingAs($this->doctor)->get("/pro/consultations/{$this->consultation->uuid}")->assertOk()->assertSee('1.42');
    }

    public function test_validated_result_can_no_longer_be_changed(): void
    {
        $result = $this->send();
        $this->complete($result);
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/valider");

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/resultats", [
            'items' => [['test_name' => 'Glycemie', 'value' => '0.90']],
        ]);

        $this->assertSame('1.42', $result->items()->firstOrFail()->value);
    }

    public function test_result_without_values_cannot_be_validated(): void
    {
        $result = $this->send();
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/prelevement");

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/valider");

        $this->assertSame('sample_collected', $result->refresh()->status);
    }

    public function test_blank_rows_of_the_form_are_ignored(): void
    {
        $result = $this->send();
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/prelevement");

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/resultats", [
            'items' => [
                ['test_name' => 'Glycemie', 'value' => '1.42', 'unit' => 'g/L', 'reference_range' => '', 'flag' => ''],
                ['test_name' => '', 'value' => '', 'unit' => '', 'reference_range' => '', 'flag' => ''],
                ['test_name' => '', 'value' => '', 'unit' => '', 'reference_range' => '', 'flag' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('completed', $result->refresh()->status);
        $this->assertSame(1, $result->items()->count());
    }

    public function test_a_row_with_a_name_but_no_value_is_refused(): void
    {
        $result = $this->send();
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/prelevement");

        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/resultats", [
            'items' => [['test_name' => 'Glycemie', 'value' => '']],
        ])->assertSessionHasErrors();

        $this->assertSame('sample_collected', $result->refresh()->status);
    }

    private function asPatient(): self
    {
        return $this->actingAs($this->patient)->withSession(['medical_pin_verified_at' => now()->timestamp]);
    }

    private function send(): LabResult
    {
        $this->asPatient()->post("/compte/soins/examens/{$this->exam->uuid}/laboratoire", ['laboratory_uuid' => $this->lab->uuid])
            ->assertRedirect('/compte/soins');

        return LabResult::where('exam_request_id', $this->exam->id)->firstOrFail();
    }

    private function complete(LabResult $result): void
    {
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/prelevement");
        $this->actingAs($this->tech)->post("/pro/laboratoire/analyses/{$result->uuid}/resultats", [
            'conclusion' => 'Glycemie elevee a controler',
            'items' => [['test_name' => 'Glycemie a jeun', 'value' => '1.42', 'unit' => 'g/L', 'reference_range' => '0.70 - 1.10', 'flag' => 'H']],
        ])->assertRedirect("/pro/laboratoire/analyses/{$result->uuid}");
    }
}
