<?php
declare(strict_types=1);

namespace Tests\Feature\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EVaxProControllerTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create(['pro_validated_at' => now()]);
    }

    // ===== T10.t11 searchPatient =====

    public function test_unverified_pro_cannot_search_returns_403(): void
    {
        $pro = User::factory()->create(['pro_validated_at' => null]);
        $this->actingAs($pro)->get('/pro/evax/search?q=test')->assertForbidden();
    }

    public function test_verified_pro_can_search_by_name(): void
    {
        $pro = $this->pro();
        User::factory()->create(['name' => 'Marie NDONG', 'nip' => 'GA-1985-19850715']);
        User::factory()->create(['name' => 'Jean MBAYE']);

        $resp = $this->actingAs($pro)->getJson('/pro/evax/search?q=ndong');
        $resp->assertOk();
        $resp->assertJsonFragment(['full_name' => 'Marie NDONG']);
        $resp->assertJsonMissing(['full_name' => 'Jean MBAYE']);
    }

    public function test_search_by_nip(): void
    {
        $pro = $this->pro();
        User::factory()->create(['name' => 'Marie NDONG', 'nip' => 'GA-1985-19850715']);
        $resp = $this->actingAs($pro)->getJson('/pro/evax/search?q=GA-1985');
        $resp->assertOk();
        $resp->assertJsonFragment(['nip' => 'GA-1985-19850715']);
    }

    // ===== T10.t12 storeVaccination =====

    public function test_pro_can_record_a_standardized_vaccination_for_a_patient(): void
    {
        $pro = $this->pro();
        $patient = User::factory()->create();
        $bcg = Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);

        $resp = $this->actingAs($pro)->postJson('/pro/evax/vaccinations', [
            'patient_uuid' => $patient->uuid,
            'vaccine_code' => 'BCG',
            'dose_number' => 1,
            'administered_at' => '2026-05-22',
            'batch_number' => 'LOT-XYZ',
        ]);

        $resp->assertCreated();
        $this->assertDatabaseHas('vaccination_records', [
            'patient_id' => $patient->id,
            'vaccine_id' => $bcg->id,
            'dose_number' => 1,
            'is_standardized' => true,
        ]);

        $record = VaccinationRecord::where('patient_id', $patient->id)->first();
        $this->assertNotNull($record->signed_at);
        $this->assertSame(1, $record->carnet_revision);
    }

    public function test_adding_second_vaccination_increments_revision(): void
    {
        $pro = $this->pro();
        $patient = User::factory()->create();
        Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);
        Vaccine::create(['code' => 'OPV0', 'name_fr' => 'OPV0', 'doses_total' => 1]);

        $this->actingAs($pro)->postJson('/pro/evax/vaccinations', [
            'patient_uuid' => $patient->uuid, 'vaccine_code' => 'BCG',
            'dose_number' => 1, 'administered_at' => '2026-05-22',
        ])->assertCreated();
        $this->actingAs($pro)->postJson('/pro/evax/vaccinations', [
            'patient_uuid' => $patient->uuid, 'vaccine_code' => 'OPV0',
            'dose_number' => 1, 'administered_at' => '2026-05-22',
        ])->assertCreated();

        $revs = VaccinationRecord::where('patient_id', $patient->id)->orderBy('id')->pluck('carnet_revision')->all();
        $this->assertSame([1, 2], $revs);
    }

    public function test_pro_can_record_a_free_text_vaccine_for_a_dependent(): void
    {
        $pro = $this->pro();
        $parent = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $parent->id, 'first_name' => 'Junior', 'last_name' => 'M',
            'date_of_birth' => '2025-01-15',
        ]);

        $resp = $this->actingAs($pro)->postJson('/pro/evax/vaccinations', [
            'dependent_uuid' => $dep->uuid,
            'vaccine_name' => 'Vaccin traditionnel local',
            'dose_number' => 1,
            'administered_at' => '2026-05-22',
        ]);

        $resp->assertCreated();
        $this->assertDatabaseHas('vaccination_records', [
            'dependent_id' => $dep->id,
            'is_standardized' => false,
            'vaccine_id' => null,
            'vaccine_name' => 'Vaccin traditionnel local',
        ]);
    }

    public function test_validation_rejects_when_neither_patient_nor_dependent(): void
    {
        $pro = $this->pro();
        $resp = $this->actingAs($pro)->postJson('/pro/evax/vaccinations', [
            'vaccine_code' => 'BCG', 'dose_number' => 1, 'administered_at' => '2026-05-22',
        ]);
        $resp->assertStatus(422);
    }

    // ===== T10.t13 resolvePatientFromQr =====

    public function test_pro_can_resolve_patient_from_identity_url(): void
    {
        $pro = $this->pro();
        $patient = User::factory()->create(['name' => 'Marie NDONG']);
        $url = url('/carnet/identity/'.$patient->carnet_qr_secret);

        $resp = $this->actingAs($pro)->postJson('/pro/evax/resolve-qr', ['qr' => $url]);
        $resp->assertOk();
        $resp->assertJsonPath('data.uuid', $patient->uuid);
        $resp->assertJsonPath('data.full_name', 'Marie NDONG');
    }

    public function test_pro_resolve_accepts_raw_secret(): void
    {
        $pro = $this->pro();
        $patient = User::factory()->create();
        $resp = $this->actingAs($pro)->postJson('/pro/evax/resolve-qr', ['qr' => $patient->carnet_qr_secret]);
        $resp->assertOk();
        $resp->assertJsonPath('data.uuid', $patient->uuid);
    }

    public function test_pro_resolve_returns_404_for_unknown_secret(): void
    {
        $pro = $this->pro();
        $this->actingAs($pro)->postJson('/pro/evax/resolve-qr', ['qr' => 'unknownsecretxxxxxxxxxxxxxxxxxxx'])
            ->assertNotFound();
    }
}
