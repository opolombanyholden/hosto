<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Exceptions\MedicalEncryptionKeyMissing;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;

/**
 * Encrypts medical data at rest with AES-256-GCM.
 *
 * The key is dedicated (MEDICAL_ENCRYPTION_KEY), separate from APP_KEY, so a
 * routine APP_KEY change cannot make medical data unreadable and a leaked
 * session key does not expose it.
 *
 * Stored format: hme1:<key id>:<payload>. The key id allows rotation, and the
 * prefix tells encrypted values from rows not migrated yet.
 *
 * @see docs/adr/0016-chiffrement-donnees-medicales.md
 */
final class MedicalCipher
{
    private const PREFIX = 'hme1:';

    private const KEY_BYTES = 32;

    private ?string $currentId = null;

    /** @var array<string, Encrypter> */
    private array $encrypters = [];

    /**
     * @param  list<string>  $previousKeys
     */
    public function __construct(?string $key, array $previousKeys = [])
    {
        $raw = $this->rawKey($key);

        if ($raw !== null) {
            $this->currentId = $this->keyId($raw);
            $this->encrypters[$this->currentId] = new Encrypter($raw, 'aes-256-gcm');
        }

        foreach ($previousKeys as $previous) {
            $rawPrevious = $this->rawKey($previous);

            if ($rawPrevious !== null) {
                $this->encrypters[$this->keyId($rawPrevious)] ??= new Encrypter($rawPrevious, 'aes-256-gcm');
            }
        }
    }

    public function encrypt(string $plaintext): string
    {
        if ($this->currentId === null) {
            throw MedicalEncryptionKeyMissing::make();
        }

        return self::PREFIX.$this->currentId.':'.$this->encrypters[$this->currentId]->encryptString($plaintext);
    }

    /**
     * A value without the prefix is a row not migrated yet: it is returned as is.
     */
    public function decrypt(string $stored): string
    {
        if (! $this->isEncrypted($stored)) {
            return $stored;
        }

        [, $keyId, $payload] = explode(':', $stored, 3);

        if ($this->encrypters === []) {
            throw MedicalEncryptionKeyMissing::make();
        }

        if (! isset($this->encrypters[$keyId])) {
            throw new DecryptException("Cle de chiffrement medicale inconnue : {$keyId}.");
        }

        return $this->encrypters[$keyId]->decryptString($payload);
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public function isCurrent(string $value): bool
    {
        return $this->currentId !== null && str_starts_with($value, self::PREFIX.$this->currentId.':');
    }

    public function isReady(): bool
    {
        return $this->currentId !== null;
    }

    private function rawKey(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $raw = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;

        return is_string($raw) && strlen($raw) === self::KEY_BYTES ? $raw : null;
    }

    private function keyId(string $raw): string
    {
        return substr(hash('sha256', $raw), 0, 8);
    }
}
