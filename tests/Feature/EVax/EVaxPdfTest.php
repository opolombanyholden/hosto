<?php
declare(strict_types=1);

namespace Tests\Feature\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EVaxPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $tmp = sys_get_temp_dir().'/evax-pdf-'.uniqid();
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

    public function test_patient_can_download_pdf_of_their_carnet(): void
    {
        $u = User::factory()->create();
        $bcg = Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);
        VaccinationRecord::create([
            'patient_id' => $u->id, 'vaccine_id' => $bcg->id, 'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG', 'is_standardized' => true, 'dose_number' => 1,
            'administered_at' => '2026-05-01', 'carnet_revision' => 1,
        ]);

        $resp = $this->actingAs($u)->get('/compte/carnet-vaccination/me/pdf');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/pdf');
        // PDF signature — regular (non-streamed) response, use getContent()
        $body = $resp->getContent();
        $this->assertStringStartsWith('%PDF', $body);
    }

    public function test_patient_can_download_pdf_of_their_dependent(): void
    {
        $u = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $u->id, 'first_name' => 'Junior', 'last_name' => 'M',
            'date_of_birth' => '2025-01-15',
        ]);
        $resp = $this->actingAs($u)->get('/compte/carnet-vaccination/dep-'.$dep->uuid.'/pdf');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_patient_cannot_download_other_users_dependent_pdf(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $u1->id, 'first_name' => 'X', 'last_name' => 'Y',
            'date_of_birth' => '2025-01-15',
        ]);
        $this->actingAs($u2)->get('/compte/carnet-vaccination/dep-'.$dep->uuid.'/pdf')
            ->assertForbidden();
    }
}
