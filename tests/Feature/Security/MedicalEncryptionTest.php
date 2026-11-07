<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Exceptions\MedicalEncryptionKeyMissing;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\MedicalCipher;
use App\Modules\Lab\Models\LabResult;
use App\Modules\Lab\Models\LabResultItem;
use App\Modules\Pharma\Models\Dispensation;
use App\Modules\Pharma\Models\DispensationItem;
use App\Modules\Pro\Models\CareAct;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\ExamRequest;
use App\Modules\Pro\Models\Prescription;
use App\Modules\Pro\Models\PrescriptionItem;
use App\Modules\Pro\Models\Treatment;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MedicalEncryptionTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'SECRET-MEDICAL';

    private User $patient;

    private Practitioner $practitioner;

    private Hosto $hosto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create();
        $this->practitioner = Practitioner::factory()->create();
        $this->hosto = Hosto::factory()->create();
    }

    public function test_consultation_is_ciphertext_in_the_database_and_readable_in_the_app(): void
    {
        $consultation = $this->consultation([
            'anamnesis' => self::SECRET, 'examen_clinique' => self::SECRET, 'diagnostic' => self::SECRET,
            'diagnostic_code' => 'B50.9', 'conduite_a_tenir' => self::SECRET, 'notes_internes' => self::SECRET,
            'vitals' => ['tension' => '12/8', 'note' => self::SECRET],
        ]);

        $this->assertColumnsAreCiphertext('consultations', $consultation->id, [
            'motif', 'anamnesis', 'examen_clinique', 'diagnostic', 'diagnostic_code', 'conduite_a_tenir', 'notes_internes', 'vitals',
        ]);

        $fresh = Consultation::findOrFail($consultation->id);
        $this->assertSame(self::SECRET, $fresh->diagnostic);
        $this->assertSame('B50.9', $fresh->diagnostic_code);
        $this->assertSame(['tension' => '12/8', 'note' => self::SECRET], $fresh->vitals);
    }

    public function test_long_clinical_text_fits_once_encrypted(): void
    {
        $motif = str_repeat('é', 255);

        $consultation = $this->consultation(['motif' => $motif]);

        $this->assertSame($motif, Consultation::findOrFail($consultation->id)->motif);
    }

    public function test_null_stays_null(): void
    {
        $consultation = $this->consultation(['diagnostic' => null]);

        $this->assertNull(DB::table('consultations')->where('id', $consultation->id)->value('diagnostic'));
        $this->assertSame(0, Consultation::whereKey($consultation->id)->whereNotNull('diagnostic')->count());
    }

    public function test_prescriptions_exams_and_care_are_ciphertext(): void
    {
        $consultation = $this->consultation();
        $common = ['consultation_id' => $consultation->id, 'practitioner_id' => $this->practitioner->id, 'patient_id' => $this->patient->id];

        $prescription = Prescription::create($common + ['reference' => Prescription::generateReference(), 'notes' => self::SECRET]);
        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id, 'medication_name' => self::SECRET, 'dosage' => self::SECRET,
            'posology' => self::SECRET, 'duration' => self::SECRET, 'instructions' => self::SECRET,
        ]);
        $exam = ExamRequest::create($common + ['exam_type' => self::SECRET, 'clinical_info' => self::SECRET, 'results' => self::SECRET]);
        $treatment = Treatment::create($common + [
            'type' => 'medication', 'description' => self::SECRET, 'instructions' => self::SECRET, 'frequency' => self::SECRET, 'duration' => self::SECRET,
        ]);
        $care = CareAct::create($common + ['care_type' => 'injection', 'description' => self::SECRET, 'instructions' => self::SECRET, 'notes' => self::SECRET]);

        $this->assertColumnsAreCiphertext('prescriptions', $prescription->id, ['notes']);
        $this->assertColumnsAreCiphertext('prescription_items', $item->id, ['medication_name', 'dosage', 'posology', 'duration', 'instructions']);
        $this->assertColumnsAreCiphertext('exam_requests', $exam->id, ['exam_type', 'clinical_info', 'results']);
        $this->assertColumnsAreCiphertext('treatments', $treatment->id, ['description', 'instructions', 'frequency', 'duration']);
        $this->assertColumnsAreCiphertext('care_acts', $care->id, ['description', 'instructions', 'notes']);
    }

    public function test_lab_and_pharmacy_data_are_ciphertext(): void
    {
        $consultation = $this->consultation();
        $exam = ExamRequest::create([
            'consultation_id' => $consultation->id, 'practitioner_id' => $this->practitioner->id,
            'patient_id' => $this->patient->id, 'exam_type' => 'NFS',
        ]);
        $result = LabResult::create([
            'exam_request_id' => $exam->id, 'hosto_id' => $this->hosto->id, 'patient_id' => $this->patient->id,
            'reference' => LabResult::generateReference(), 'conclusion' => self::SECRET, 'notes' => self::SECRET,
        ]);
        $line = LabResultItem::create([
            'lab_result_id' => $result->id, 'test_name' => self::SECRET, 'value' => self::SECRET,
            'reference_range' => self::SECRET, 'comment' => self::SECRET,
        ]);
        $order = Dispensation::create([
            'hosto_id' => $this->hosto->id, 'patient_id' => $this->patient->id,
            'reference' => Dispensation::generateReference(), 'notes' => self::SECRET,
        ]);
        $orderLine = DispensationItem::create([
            'dispensation_id' => $order->id, 'medication_name' => self::SECRET, 'dosage' => self::SECRET, 'substitution_reason' => self::SECRET,
        ]);

        $this->assertColumnsAreCiphertext('lab_results', $result->id, ['conclusion', 'notes']);
        $this->assertColumnsAreCiphertext('lab_result_items', $line->id, ['test_name', 'value', 'reference_range', 'comment']);
        $this->assertColumnsAreCiphertext('dispensations', $order->id, ['notes']);
        $this->assertColumnsAreCiphertext('dispensation_items', $orderLine->id, ['medication_name', 'dosage', 'substitution_reason']);
    }

    public function test_appointment_reason_and_patient_medical_profile_are_ciphertext(): void
    {
        $slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->hosto->id,
            'date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30', 'duration_minutes' => 30,
        ]);
        $appointment = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id, 'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id, 'reason' => self::SECRET, 'notes' => self::SECRET,
            'third_party_notes' => self::SECRET, 'cancellation_reason' => self::SECRET,
        ]);
        $this->patient->update([
            'allergies' => self::SECRET, 'chronic_conditions' => self::SECRET, 'current_medications' => self::SECRET,
            'surgical_history' => self::SECRET, 'family_history' => self::SECRET, 'disabilities' => self::SECRET,
        ]);

        $this->assertColumnsAreCiphertext('appointments', $appointment->id, ['reason', 'notes', 'third_party_notes', 'cancellation_reason']);
        $this->assertColumnsAreCiphertext('users', $this->patient->id, [
            'allergies', 'chronic_conditions', 'current_medications', 'surgical_history', 'family_history', 'disabilities',
        ]);
        $this->assertSame(self::SECRET, User::findOrFail($this->patient->id)->allergies);
    }

    public function test_without_key_nothing_is_written_in_clear(): void
    {
        $this->app->instance(MedicalCipher::class, new MedicalCipher(null));

        try {
            $this->consultation();
            $this->fail('La consultation a ete enregistree sans cle.');
        } catch (MedicalEncryptionKeyMissing) {
            $this->assertSame(0, Consultation::where('patient_id', $this->patient->id)->count());
        }
    }

    public function test_legacy_plaintext_rows_stay_readable(): void
    {
        $consultation = $this->consultation();
        DB::table('consultations')->where('id', $consultation->id)->update([
            'motif' => 'Ancien motif', 'vitals' => '{"tension":"13/8"}',
        ]);

        $fresh = Consultation::findOrFail($consultation->id);

        $this->assertSame('Ancien motif', $fresh->motif);
        $this->assertSame(['tension' => '13/8'], $fresh->vitals);
    }

    public function test_patient_search_still_finds_encrypted_motif_and_diagnostic(): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $this->patient->roles()->attach($role);
        $other = User::factory()->create();
        $this->consultation(['motif' => 'Fièvre persistante']);
        $this->consultation(['motif' => 'Bilan', 'diagnostic' => 'Paludisme']);
        $this->consultation(['motif' => 'Toux']);
        $this->consultation(['motif' => 'Fièvre', 'patient_id' => $other->id]);

        $this->actingAs($this->patient);

        $this->getJson('/compte/api/consultations?q=fievre')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.motif', 'Fièvre persistante');
        $this->getJson('/compte/api/consultations?q=PALUD')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/compte/api/consultations')->assertOk()->assertJsonPath('meta.total', 3);
    }

    public function test_patient_appointment_search_finds_encrypted_reason(): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $this->patient->roles()->attach($role);
        $slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->hosto->id,
            'date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30', 'duration_minutes' => 30,
        ]);
        Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id, 'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id, 'reason' => 'Contrôle tension',
        ]);

        $this->actingAs($this->patient);

        $this->getJson('/compte/api/rendez-vous?q=controle')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/compte/api/rendez-vous?q=inexistant')->assertOk()->assertJsonPath('meta.total', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function consultation(array $attributes = []): Consultation
    {
        return Consultation::create($attributes + [
            'practitioner_id' => $this->practitioner->id, 'patient_id' => $this->patient->id, 'hosto_id' => $this->hosto->id,
            'status' => 'completed', 'motif' => self::SECRET,
        ]);
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertColumnsAreCiphertext(string $table, int $id, array $columns): void
    {
        $row = (array) DB::table($table)->where('id', $id)->first();

        foreach ($columns as $column) {
            $this->assertStringStartsWith('hme1:', (string) $row[$column], "{$table}.{$column} est en clair");
            $this->assertStringNotContainsString(self::SECRET, (string) $row[$column]);
        }
    }
}
