<?php

declare(strict_types=1);

namespace Tests\Feature\Lab;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Lab\Models\LabResult;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\ExamRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LabResultsAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = User::factory()->create();
    }

    public function test_result_detail_requires_authentication(): void
    {
        $result = $this->labResult('transmitted');

        $this->getJson("/api/v1/lab/results/{$result->uuid}")->assertUnauthorized();
    }

    public function test_another_user_cannot_read_a_result(): void
    {
        $result = $this->labResult('transmitted');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/lab/results/{$result->uuid}")->assertNotFound();
    }

    public function test_patient_reads_own_transmitted_result(): void
    {
        $result = $this->labResult('transmitted');
        Sanctum::actingAs($this->patient);

        $this->getJson("/api/v1/lab/results/{$result->uuid}")->assertOk()->assertJsonPath('data.reference', $result->reference);
    }

    public function test_patient_cannot_read_a_result_before_validation(): void
    {
        $result = $this->labResult('completed');
        Sanctum::actingAs($this->patient);

        $this->getJson("/api/v1/lab/results/{$result->uuid}")->assertNotFound();
        $this->getJson('/api/v1/lab/results')->assertOk()->assertJsonPath('meta.total', 0);
    }

    private function labResult(string $status): LabResult
    {
        $practitioner = Practitioner::factory()->create();
        $hosto = Hosto::factory()->create();
        $consultation = Consultation::create([
            'practitioner_id' => $practitioner->id, 'patient_id' => $this->patient->id, 'hosto_id' => $hosto->id,
            'status' => 'completed', 'motif' => 'Bilan',
        ]);
        $exam = ExamRequest::create([
            'consultation_id' => $consultation->id, 'practitioner_id' => $practitioner->id,
            'patient_id' => $this->patient->id, 'exam_type' => 'NFS',
        ]);

        return LabResult::create([
            'exam_request_id' => $exam->id, 'hosto_id' => $hosto->id, 'patient_id' => $this->patient->id,
            'reference' => LabResult::generateReference(), 'status' => $status,
        ]);
    }
}
