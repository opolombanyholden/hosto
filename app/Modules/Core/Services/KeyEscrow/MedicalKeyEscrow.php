<?php

declare(strict_types=1);

namespace App\Modules\Core\Services\KeyEscrow;

use App\Modules\Core\Exceptions\MedicalEncryptionKeyMissing;
use App\Modules\Core\Services\MedicalCipher;
use App\Modules\Core\Support\MedicalFields;
use Illuminate\Support\Facades\DB;

/**
 * Splits the medical key into custodian shares and rebuilds it from them.
 *
 * Share format: HOSTO1-<key id>-<threshold>-<shares>-<x>-<hex payload>-<checksum>.
 * The checksum catches a mistyped share; the key id rejects shares of another
 * key; the key id of the rebuilt key proves the result is the right key.
 *
 * @see docs/adr/0017-sequestre-cle-chiffrement.md
 */
final class MedicalKeyEscrow
{
    private const PATTERN = '/HOSTO1-([0-9a-f]{8})-(\d{1,2})-(\d{1,2})-(\d{1,2})-([0-9a-f]{64})-([0-9a-f]{8})/';

    public function __construct(private readonly ShamirSecretSharing $shamir) {}

    public function currentKeyId(): ?string
    {
        $raw = MedicalCipher::decodeKey(config('hosto.medical_encryption.key'));

        return $raw === null ? null : MedicalCipher::keyIdOf($raw);
    }

    /**
     * @return array<int, string> formatted shares keyed by x
     */
    public function splitCurrentKey(int $shares, int $threshold): array
    {
        $raw = MedicalCipher::decodeKey(config('hosto.medical_encryption.key'));

        if ($raw === null) {
            throw MedicalEncryptionKeyMissing::make();
        }

        $keyId = MedicalCipher::keyIdOf($raw);
        $result = [];

        foreach ($this->shamir->split($raw, $shares, $threshold) as $x => $bytes) {
            $body = sprintf('HOSTO1-%s-%d-%d-%d-%s', $keyId, $threshold, $shares, $x, bin2hex($bytes));
            $result[$x] = $body.'-'.$this->checksum(strtolower($body));
        }

        return $result;
    }

    /**
     * @return array{key_id: string, threshold: int, shares: int, x: int, bytes: string}
     */
    public function parse(string $input): array
    {
        $normalized = strtolower((string) preg_replace('/\s+/', '', $input));
        $normalized = (string) preg_replace('/^hosto1-/', 'HOSTO1-', $normalized);

        if (preg_match('/^'.trim(self::PATTERN, '/').'$/', $normalized, $m) !== 1) {
            throw new InvalidShare('Fragment illisible : verifiez qu\'il est recopie en entier.');
        }

        $body = strtolower(substr($normalized, 0, -9));

        if (! hash_equals($this->checksum($body), $m[6])) {
            throw new InvalidShare('Fragment illisible : un caractere est errone (somme de controle invalide).');
        }

        [$threshold, $shares, $x] = [(int) $m[2], (int) $m[3], (int) $m[4]];

        if ($threshold < 2 || $threshold > $shares || $x < 1 || $x > $shares) {
            throw new InvalidShare('Fragment illisible : parametres incoherents.');
        }

        return ['key_id' => $m[1], 'threshold' => $threshold, 'shares' => $shares, 'x' => $x, 'bytes' => (string) hex2bin($m[5])];
    }

    /**
     * Rebuilds the raw key and proves it is the key the shares were made from.
     *
     * @param  list<array{key_id: string, threshold: int, shares: int, x: int, bytes: string}>  $parsed
     */
    public function rebuild(array $parsed): string
    {
        $keyed = [];
        foreach ($parsed as $share) {
            $keyed[$share['x']] = $share['bytes'];
        }

        $raw = $this->shamir->combine($keyed);

        if (MedicalCipher::keyIdOf($raw) !== $parsed[0]['key_id']) {
            throw new InvalidShare('Les fragments ne reconstituent pas la cle attendue : au moins un fragment est faux.');
        }

        return $raw;
    }

    /**
     * Pulls the share out of a custodian sheet.
     */
    public function extractShare(string $sheet): string
    {
        if (preg_match(self::PATTERN, $sheet, $m) !== 1) {
            throw new InvalidShare('Aucun fragment trouve.');
        }

        return $m[0];
    }

    /**
     * One stored value encrypted with the given key, to prove a rebuilt key decrypts real data.
     */
    public function sampleValueFor(string $keyId): ?string
    {
        foreach (MedicalFields::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $value = DB::table($table)->where($column, 'like', "hme1:{$keyId}:%")->value($column);

                if ($value !== null) {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    /**
     * Key ids found in stored medical data.
     *
     * @return list<string>
     */
    public function keyIdsInData(): array
    {
        $ids = [];

        foreach (MedicalFields::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $found = DB::table($table)
                    ->where($column, 'like', 'hme1:%')
                    ->selectRaw("distinct substring({$column} from 6 for 8) as key_id")
                    ->pluck('key_id');

                foreach ($found as $id) {
                    $ids[(string) $id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function checksum(string $body): string
    {
        return substr(hash('sha256', $body), 0, 8);
    }
}
