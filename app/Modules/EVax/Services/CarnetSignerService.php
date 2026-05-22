<?php
declare(strict_types=1);

namespace App\Modules\EVax\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Signs and verifies EVax carnet tokens using ES256 (EC P-256).
 *
 * To keep QR-code tokens scanner-friendly (< 2500 bytes even for 50+ vaccinations),
 * the full payload is gzip-compressed and stored in a single JWT claim (`d`).
 * verify() transparently decompresses and returns the original payload array.
 */
final class CarnetSignerService
{
    /**
     * Sign an arbitrary payload array.
     * The payload is gzip-compressed before being placed in the JWT so that
     * large vaccination lists stay well under the 2500-byte scanner limit.
     */
    public function sign(array $payload): string
    {
        $kid = config('hosto.carnet.kid');
        $privateKeyPath = config('hosto.carnet.private_key_path');

        if (! is_readable($privateKeyPath)) {
            throw new \RuntimeException("Carnet private key not readable at {$privateKeyPath}");
        }
        $privateKey = file_get_contents($privateKeyPath);

        $compressed = base64_encode(gzcompress(json_encode($payload), 9));

        return JWT::encode(['d' => $compressed], $privateKey, 'ES256', $kid);
    }

    /**
     * Verify a signed carnet JWS token.
     *
     * @return array<string, mixed>|null  The original payload, or null on any failure.
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

            $decoded = (array) JWT::decode($jws, $keys);

            if (! isset($decoded['d'])) {
                return null;
            }

            $json = gzuncompress(base64_decode($decoded['d']));
            if ($json === false) {
                return null;
            }

            $payload = json_decode($json, true);
            if (! is_array($payload)) {
                return null;
            }

            return $payload;
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
