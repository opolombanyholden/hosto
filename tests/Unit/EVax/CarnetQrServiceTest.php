<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Models\User;
use App\Modules\EVax\Services\CarnetQrService;
use App\Modules\EVax\Services\CarnetSignerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CarnetQrServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarnetQrService $qr;
    private string $tmpPrivate;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = sys_get_temp_dir().'/evax-qr-'.uniqid();
        mkdir($tmp);
        $this->tmpPrivate = $tmp.'/priv.pem';
        $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($res, $priv);
        $pub = openssl_pkey_get_details($res)['key'];
        file_put_contents($this->tmpPrivate, $priv);
        config([
            'hosto.carnet.kid' => 'test',
            'hosto.carnet.private_key_path' => $this->tmpPrivate,
            'hosto.carnet.public_keys' => ['test' => $pub],
            'hosto.carnet.verify_base_url' => 'https://hosto.ga/c/v',
        ]);
        $this->qr = new CarnetQrService(new CarnetSignerService());
    }

    public function test_identity_qr_contains_short_url_with_secret(): void
    {
        $u = User::factory()->create();
        $svg = $this->qr->identityQrSvg($u);
        $this->assertStringContainsString('<svg', $svg);
        $payload = $this->qr->identityUrl($u);
        $this->assertStringContainsString('/carnet/identity/', $payload);
        $this->assertStringContainsString($u->carnet_qr_secret, $payload);
    }

    public function test_verification_qr_contains_url_and_jws_fragment(): void
    {
        $u = User::factory()->create();
        $url = $this->qr->verificationUrl($u);
        $this->assertStringContainsString('https://hosto.ga/c/v/'.$u->carnet_qr_secret, $url);
        $this->assertStringContainsString('#jws=', $url);
    }

    public function test_verification_url_under_2kb(): void
    {
        $u = User::factory()->create();
        $url = $this->qr->verificationUrl($u);
        $this->assertLessThan(2048, strlen($url));
    }
}
