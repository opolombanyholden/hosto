<?php
declare(strict_types=1);

namespace App\Modules\EVax\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Signs and verifies EVax carnet tokens using ES256 (EC P-256).
 *
 * Emits standard JWT/JWS tokens — readable by any JWT-aware tool (jwt.io,
 * OpenID-Connect libraries, etc.). The payload size is bounded by
 * CarnetQrService which truncates the `vacc` array to 15 entries.
 */
final class CarnetSignerService
{
    public function sign(array $payload): string
    {
        $kid = config('hosto.carnet.kid');
        $privateKeyPath = config('hosto.carnet.private_key_path');

        if (! is_readable($privateKeyPath)) {
            throw new \RuntimeException("Carnet private key not readable at {$privateKeyPath}");
        }
        $privateKey = file_get_contents($privateKeyPath);

        return JWT::encode($payload, $privateKey, 'ES256', $kid);
    }

    /**
     * @return array<string, mixed>|null  The verified payload, or null on any failure.
     */
    public function verify(string $jws): ?array
    {
        try {
            $publicKeys = config('hosto.carnet.public_keys', []);
            $keys = [];
            foreach ($publicKeys as $kid => $pem) {
                if (! $pem) {
                    continue;
                }
                $keys[$kid] = new Key($pem, 'ES256');
            }
            if (empty($keys)) {
                return null;
            }
            return (array) JWT::decode($jws, $keys);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function activeKid(): string
    {
        return (string) config('hosto.carnet.kid', 'hosto-dev');
    }

    /** @return array<string, mixed> JWKS-formatted public key set. */
    public function jwks(): array
    {
        $keys = [];
        foreach (config('hosto.carnet.public_keys', []) as $kid => $pem) {
            if (! $pem) {
                continue;
            }
            $pkey = openssl_pkey_get_public($pem);
            if (! $pkey) {
                continue;
            }
            $keyDetails = openssl_pkey_get_details($pkey);
            if (! $keyDetails || ! isset($keyDetails['ec'])) {
                continue;
            }
            $keys[] = [
                'kid' => $kid,
                'kty' => 'EC',
                'crv' => 'P-256',
                'alg' => 'ES256',
                'use' => 'sig',
                'x' => rtrim(strtr(base64_encode($keyDetails['ec']['x']), '+/', '-_'), '='),
                'y' => rtrim(strtr(base64_encode($keyDetails['ec']['y']), '+/', '-_'), '='),
            ];
        }
        return ['keys' => $keys];
    }
}
