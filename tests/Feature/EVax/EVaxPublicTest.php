<?php
declare(strict_types=1);

namespace Tests\Feature\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EVaxPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_renders_carnet_for_valid_secret(): void
    {
        $u = User::factory()->create(['name' => 'Marie NDONG']);
        $bcg = Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);
        VaccinationRecord::create([
            'patient_id' => $u->id, 'vaccine_id' => $bcg->id, 'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG', 'is_standardized' => true, 'dose_number' => 1,
            'administered_at' => '2026-05-01', 'carnet_revision' => 1,
        ]);
        $resp = $this->get('/c/v/'.$u->carnet_qr_secret);
        $resp->assertOk();
        $resp->assertSee('Marie NDONG');
        $resp->assertSee('BCG');
        $resp->assertSee('Vérifié');
    }

    public function test_verify_renders_dependent_carnet(): void
    {
        $u = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $u->id, 'first_name' => 'Junior', 'last_name' => 'M',
            'date_of_birth' => '2025-01-15',
        ]);
        $resp = $this->get('/c/v/'.$dep->carnet_qr_secret);
        $resp->assertOk();
        $resp->assertSee('Junior M');
    }

    public function test_verify_returns_404_for_unknown_secret(): void
    {
        // 32-char string that doesn't match any user nor dependent
        $this->get('/c/v/abcdefghijklmnopqrstuvwxyz123456')->assertNotFound();
    }

    public function test_identity_renders_minimal_patient_info(): void
    {
        $u = User::factory()->create(['name' => 'Jean MBAYE']);
        $resp = $this->get('/carnet/identity/'.$u->carnet_qr_secret);
        $resp->assertOk();
        $resp->assertSee('Jean MBAYE');
    }

    public function test_jwks_endpoint_returns_keys_when_configured(): void
    {
        $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $pub = openssl_pkey_get_details($res)['key'];
        config(['hosto.carnet.public_keys' => ['kid-test' => $pub]]);

        $resp = $this->get('/.well-known/hosto/carnet-keys.json');
        $resp->assertOk();
        $resp->assertJsonStructure(['keys' => [['kid', 'kty', 'crv', 'alg', 'use', 'x', 'y']]]);
        $resp->assertJsonPath('keys.0.kid', 'kid-test');
        $resp->assertJsonPath('keys.0.alg', 'ES256');
    }

    public function test_jwks_endpoint_returns_empty_keys_when_unconfigured(): void
    {
        config(['hosto.carnet.public_keys' => []]);
        $resp = $this->get('/.well-known/hosto/carnet-keys.json');
        $resp->assertOk();
        $resp->assertExactJson(['keys' => []]);
    }
}
