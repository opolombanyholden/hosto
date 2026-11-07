<?php

declare(strict_types=1);

namespace App\Modules\Core\Services\KeyEscrow;

use InvalidArgumentException;

/**
 * Shamir's secret sharing over GF(256), byte by byte.
 *
 * The secret is the constant term of a random polynomial of degree k-1. Each
 * share is a point of that polynomial: any k points rebuild it, k-1 points say
 * nothing about the secret.
 */
final class ShamirSecretSharing
{
    public const MAX_SHARES = 10;

    /** @var array<int, int> */
    private array $exp = [];

    /** @var array<int, int> */
    private array $log = [];

    public function __construct()
    {
        // GF(2^8) with the AES polynomial x^8 + x^4 + x^3 + x + 1 and generator 3.
        $value = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->exp[$i] = $value;
            $this->log[$value] = $i;
            $value ^= $this->xtime($value);
        }
    }

    /**
     * @return array<int, string> shares keyed by their x coordinate (1..n)
     */
    public function split(string $secret, int $shares, int $threshold): array
    {
        if ($threshold < 2 || $threshold > $shares || $shares > self::MAX_SHARES) {
            throw new InvalidArgumentException('Il faut 2 <= seuil <= nombre de fragments <= '.self::MAX_SHARES.'.');
        }

        $result = array_fill(1, $shares, '');

        foreach (str_split($secret) as $byte) {
            $coefficients = [ord($byte)];
            foreach (str_split(random_bytes($threshold - 1)) as $random) {
                $coefficients[] = ord($random);
            }

            for ($x = 1; $x <= $shares; $x++) {
                $y = 0;
                for ($i = $threshold - 1; $i >= 0; $i--) {
                    $y = $this->mul($y, $x) ^ $coefficients[$i];
                }
                $result[$x] .= chr($y);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $shares  keyed by x coordinate
     */
    public function combine(array $shares): string
    {
        if (count($shares) < 2) {
            throw new InvalidArgumentException('Il faut au moins deux fragments.');
        }

        $length = null;
        foreach ($shares as $x => $share) {
            if ($x < 1 || $x > 255) {
                throw new InvalidArgumentException("Coordonnee de fragment invalide : {$x}.");
            }
            $length ??= strlen($share);
            if (strlen($share) !== $length) {
                throw new InvalidArgumentException('Les fragments n\'ont pas la meme longueur.');
            }
        }
        if (count(array_unique($shares)) !== count($shares)) {
            throw new InvalidArgumentException('Le meme fragment a ete fourni deux fois.');
        }

        $secret = '';
        for ($position = 0; $position < $length; $position++) {
            $value = 0;
            foreach ($shares as $xi => $share) {
                $numerator = 1;
                $denominator = 1;
                foreach (array_keys($shares) as $xj) {
                    if ($xj !== $xi) {
                        $numerator = $this->mul($numerator, $xj);
                        $denominator = $this->mul($denominator, $xj ^ $xi);
                    }
                }
                $value ^= $this->mul(ord($share[$position]), $this->div($numerator, $denominator));
            }
            $secret .= chr($value);
        }

        return $secret;
    }

    private function xtime(int $value): int
    {
        $value <<= 1;

        return ($value & 0x100) !== 0 ? ($value ^ 0x11B) & 0xFF : $value;
    }

    private function mul(int $a, int $b): int
    {
        return $a === 0 || $b === 0 ? 0 : $this->exp[($this->log[$a] + $this->log[$b]) % 255];
    }

    private function div(int $a, int $b): int
    {
        return $a === 0 ? 0 : $this->exp[($this->log[$a] - $this->log[$b] + 255) % 255];
    }
}
