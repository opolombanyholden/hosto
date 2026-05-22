<?php
declare(strict_types=1);

namespace Tests\Feature\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EVaxPatientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Configure carnet keys for QR generation in tests
        $tmp = sys_get_temp_dir().'/evax-pat-'.uniqid();
        mkdir($tmp);
        $priv = $tmp.'/priv.pem';
        $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($res, $pem);
        $pub = openssl_pkey_get_details($res)['key'];
        file_put_contents($priv, $pem);
        config([
            'hosto.carnet.kid' => 'test',
            'hosto.carnet.private_key_path' => $priv,
            'hosto.carnet.public_keys' => ['test' => $pub],
            'hosto.carnet.verify_base_url' => 'https://hosto.test/c/v',
        ]);
    }

    public function test_patient_can_view_their_carnet(): void
    {
        $u = User::factory()->create(['name' => 'Marie NDONG']);
        $bcg = Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);
        VaccinationRecord::create([
            'patient_id' => $u->id, 'vaccine_id' => $bcg->id, 'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG', 'is_standardized' => true, 'dose_number' => 1,
            'administered_at' => '2026-05-01', 'carnet_revision' => 1,
        ]);

        $resp = $this->actingAs($u)->get('/compte/carnet-vaccination');
        $resp->assertOk();
        $resp->assertSee('Marie NDONG');
        $resp->assertSee('BCG');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/compte/carnet-vaccination')->assertRedirect('/compte/connexion');
    }

    public function test_user_can_create_a_dependent(): void
    {
        $u = User::factory()->create();
        $resp = $this->actingAs($u)->post('/compte/carnet-vaccination/dependents', [
            'first_name' => 'Junior', 'last_name' => 'NDONG',
            'date_of_birth' => '2025-01-15', 'gender' => 'male',
        ]);
        $resp->assertRedirect('/compte/carnet-vaccination/dependents');
        $this->assertDatabaseHas('dependents', [
            'user_id' => $u->id, 'first_name' => 'Junior',
        ]);
    }

    public function test_user_cannot_access_another_users_dependent(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $u1->id, 'first_name' => 'X', 'last_name' => 'Y',
            'date_of_birth' => '2025-01-15',
        ]);
        $this->actingAs($u2)->get('/compte/carnet-vaccination/dependent/'.$dep->uuid)
             ->assertForbidden();
    }

    public function test_user_can_delete_their_dependent(): void
    {
        $u = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $u->id, 'first_name' => 'X', 'last_name' => 'Y',
            'date_of_birth' => '2025-01-15',
        ]);
        $this->actingAs($u)->delete('/compte/carnet-vaccination/dependents/'.$dep->uuid)
             ->assertRedirect();
        $this->assertSoftDeleted($dep);
    }
}
