<?php

declare(strict_types=1);

namespace Tests\Feature\Compte;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\EVax\Models\VaccinationRecord;
use App\Modules\Pro\Models\CareAct;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\ExamRequest;
use App\Modules\Pro\Models\Treatment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class DossierCountersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dossier_cards_show_real_counts_for_this_patient_only(): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $patient = User::factory()->create();
        $patient->roles()->attach($role);
        $other = User::factory()->create();
        $practitioner = Practitioner::factory()->create();
        $hosto = Hosto::factory()->create();

        $consultation = fn (User $user, ?string $diagnostic) => Consultation::create([
            'practitioner_id' => $practitioner->id, 'patient_id' => $user->id, 'hosto_id' => $hosto->id,
            'status' => 'completed', 'motif' => 'Suivi', 'diagnostic' => $diagnostic,
        ]);
        $first = $consultation($patient, 'Paludisme');
        $consultation($patient, null);
        $foreign = $consultation($other, 'Autre');

        $common = ['practitioner_id' => $practitioner->id];
        ExamRequest::create($common + ['consultation_id' => $first->id, 'patient_id' => $patient->id, 'exam_type' => 'biologie', 'exam_name' => 'NFS', 'name' => 'NFS']);
        Treatment::create($common + ['consultation_id' => $first->id, 'patient_id' => $patient->id, 'type' => 'medication', 'description' => 'ACT', 'status' => 'active']);
        Treatment::create($common + ['consultation_id' => $first->id, 'patient_id' => $patient->id, 'type' => 'medication', 'description' => 'Ancien', 'status' => 'completed']);
        Treatment::create($common + ['consultation_id' => $foreign->id, 'patient_id' => $other->id, 'type' => 'medication', 'description' => 'Autre', 'status' => 'active']);
        CareAct::create($common + ['consultation_id' => $first->id, 'patient_id' => $patient->id, 'care_type' => 'injection', 'description' => 'IM']);
        VaccinationRecord::create(['patient_id' => $patient->id, 'vaccine_name' => 'BCG', 'dose_number' => 1, 'administered_at' => now()]);

        $this->actingAs($patient)->get('/compte/dossier-medical')
            ->assertOk()
            ->assertSee('1 diagnostic')
            ->assertSee('1 examen')
            ->assertSee('1 en cours')
            ->assertSee('1 vaccin')
            ->assertSee('1 soin')
            ->assertDontSee('Hospitalisations');
    }
}
