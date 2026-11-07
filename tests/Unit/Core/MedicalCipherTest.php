<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Exceptions\MedicalEncryptionKeyMissing;
use App\Modules\Core\Services\MedicalCipher;
use Illuminate\Contracts\Encryption\DecryptException;
use Tests\TestCase;

final class MedicalCipherTest extends TestCase
{
    public function test_round_trip_never_exposes_the_plaintext(): void
    {
        $cipher = new MedicalCipher($this->key('a'));

        $stored = $cipher->encrypt('Paludisme grave');

        $this->assertStringNotContainsString('Paludisme', $stored);
        $this->assertStringStartsWith('hme1:', $stored);
        $this->assertSame('Paludisme grave', $cipher->decrypt($stored));
    }

    public function test_same_text_gives_a_different_ciphertext_each_time(): void
    {
        $cipher = new MedicalCipher($this->key('a'));

        $this->assertNotSame($cipher->encrypt('VIH'), $cipher->encrypt('VIH'));
    }

    public function test_binary_content_survives(): void
    {
        $cipher = new MedicalCipher($this->key('a'));
        $bytes = random_bytes(4096);

        $this->assertSame($bytes, $cipher->decrypt($cipher->encrypt($bytes)));
    }

    public function test_tampered_data_is_rejected(): void
    {
        $cipher = new MedicalCipher($this->key('a'));
        $stored = $cipher->encrypt('Diabete');

        $this->expectException(DecryptException::class);

        $cipher->decrypt(substr($stored, 0, -6).'AAAAAA');
    }

    public function test_previous_key_still_decrypts_and_is_flagged_for_rotation(): void
    {
        $old = new MedicalCipher($this->key('old'));
        $stored = $old->encrypt('Asthme');

        $new = new MedicalCipher($this->key('new'), [$this->key('old')]);

        $this->assertSame('Asthme', $new->decrypt($stored));
        $this->assertFalse($new->isCurrent($stored));
        $this->assertTrue($new->isCurrent($new->encrypt('Asthme')));
    }

    public function test_unknown_key_cannot_decrypt(): void
    {
        $stored = (new MedicalCipher($this->key('a')))->encrypt('Secret');

        $this->expectException(DecryptException::class);

        (new MedicalCipher($this->key('b')))->decrypt($stored);
    }

    public function test_legacy_plaintext_is_returned_as_is(): void
    {
        $cipher = new MedicalCipher($this->key('a'));

        $this->assertSame('ancien texte', $cipher->decrypt('ancien texte'));
        $this->assertFalse($cipher->isEncrypted('ancien texte'));
    }

    public function test_missing_key_refuses_to_encrypt(): void
    {
        $this->expectException(MedicalEncryptionKeyMissing::class);

        (new MedicalCipher(null))->encrypt('Paludisme');
    }

    public function test_key_of_the_wrong_size_is_refused(): void
    {
        $this->expectException(MedicalEncryptionKeyMissing::class);

        (new MedicalCipher('base64:'.base64_encode('trop-court')))->encrypt('Paludisme');
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }
}
