<?php

declare(strict_types=1);

namespace Tests\Feature\Compte;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MedicalRecordGrantsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(
            ['slug' => 'patient'],
            ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']
        );
    }

    private function makePatient(): User
    {
        $u = User::factory()->create(['phone_verified_at' => now()]);
        $u->roles()->attach(Role::where('slug', 'patient')->first());

        return $u;
    }

    public function test_patient_can_list_their_active_grants(): void
    {
        $p = $this->makePatient();
        $prac = Practitioner::factory()->create();
        MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->get('/compte/dossier/partages');
        $resp->assertOk();
        $resp->assertSeeText($prac->full_name);
    }

    public function test_patient_can_revoke_grant(): void
    {
        $p = $this->makePatient();
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->post('/compte/dossier/partages/'.$g->uuid.'/revoke');
        $resp->assertRedirect();
        $this->assertNotNull($g->fresh()->revoked_at);
    }

    public function test_patient_cannot_revoke_others_grant(): void
    {
        $p1 = $this->makePatient();
        $p2 = $this->makePatient();
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p1->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p2)->post('/compte/dossier/partages/'.$g->uuid.'/revoke');
        $resp->assertForbidden();
    }

    public function test_patient_can_view_access_history(): void
    {
        $p = $this->makePatient();
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->get('/compte/dossier/partages/'.$g->uuid.'/historique');
        $resp->assertOk();
    }
}
