<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Modules\EVax\Services\CarnetSignerService;
use Tests\TestCase;

final class CarnetSignerServiceTest extends TestCase
{
    private CarnetSignerService $svc;
    private string $tmpPrivate;
    private string $tmpPublic;
    private string $kid = 'test-kid';

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = sys_get_temp_dir().'/evax-signer-'.uniqid();
        mkdir($tmp);
        $this->tmpPrivate = $tmp.'/priv.pem';
        $this->tmpPublic = $tmp.'/pub.pem';

        $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($res, $priv);
        $pub = openssl_pkey_get_details($res)['key'];
        file_put_contents($this->tmpPrivate, $priv);
        file_put_contents($this->tmpPublic, $pub);

        config([
            'hosto.carnet.kid' => $this->kid,
            'hosto.carnet.private_key_path' => $this->tmpPrivate,
            'hosto.carnet.public_keys' => [$this->kid => $pub],
        ]);

        $this->svc = new CarnetSignerService();
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpPrivate);
        @unlink($this->tmpPublic);
        @rmdir(dirname($this->tmpPrivate));
        parent::tearDown();
    }

    public function test_sign_then_verify_round_trip_succeeds(): void
    {
        $payload = ['sub' => 'abcd1234', 'rev' => 3, 'vacc' => []];
        $jws = $this->svc->sign($payload);
        $this->assertIsString($jws);
        $verified = $this->svc->verify($jws);
        $this->assertSame('abcd1234', $verified['sub']);
        $this->assertSame(3, $verified['rev']);
    }

    public function test_verify_returns_null_on_tampered_payload(): void
    {
        $jws = $this->svc->sign(['sub' => 'abc']);
        $parts = explode('.', $jws);
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"hacked"}'), '+/', '-_'), '=');
        $tampered = implode('.', $parts);
        $this->assertNull($this->svc->verify($tampered));
    }

    public function test_verify_returns_null_on_garbage_input(): void
    {
        $this->assertNull($this->svc->verify('not-a-jws'));
    }

    public function test_payload_under_size_limit_for_50_vaccinations(): void
    {
        $vacc = [];
        for ($i = 0; $i < 50; $i++) {
            $vacc[] = ['c' => 'BCG', 'd' => '2024-01-15', 'n' => $i + 1, 'std' => true];
        }
        $jws = $this->svc->sign(['sub' => 'abc', 'rev' => 1, 'vacc' => $vacc]);
        $this->assertLessThan(2500, strlen($jws), 'JWS must stay scanner-friendly');
    }
}
