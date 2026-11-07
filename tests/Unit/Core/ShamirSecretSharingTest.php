<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Services\KeyEscrow\ShamirSecretSharing;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ShamirSecretSharingTest extends TestCase
{
    private ShamirSecretSharing $shamir;

    protected function setUp(): void
    {
        $this->shamir = new ShamirSecretSharing;
    }

    public function test_every_subset_of_threshold_size_rebuilds_the_secret(): void
    {
        $secret = random_bytes(32);
        $shares = $this->shamir->split($secret, 5, 3);

        $this->assertCount(5, $shares);

        foreach ($this->combinations(5, 3) as $subset) {
            $picked = array_combine($subset, array_map(fn (int $x) => $shares[$x], $subset));
            $this->assertSame($secret, $this->shamir->combine($picked), 'Sous-ensemble '.implode(',', $subset));
        }
    }

    public function test_more_shares_than_needed_still_work(): void
    {
        $secret = random_bytes(32);
        $shares = $this->shamir->split($secret, 5, 3);

        $this->assertSame($secret, $this->shamir->combine($shares));
    }

    public function test_fewer_shares_than_the_threshold_do_not_give_the_secret(): void
    {
        $secret = random_bytes(32);
        $shares = $this->shamir->split($secret, 5, 3);

        foreach ($this->combinations(5, 2) as $subset) {
            $picked = array_combine($subset, array_map(fn (int $x) => $shares[$x], $subset));
            $this->assertNotSame($secret, $this->shamir->combine($picked));
        }
    }

    public function test_a_share_reveals_nothing_of_the_secret(): void
    {
        $secret = str_repeat("\x00", 32);
        $first = $this->shamir->split($secret, 5, 3);
        $second = $this->shamir->split($secret, 5, 3);

        $this->assertNotSame($first[1], $second[1], 'Les coefficients doivent etre aleatoires.');
        $this->assertNotSame($secret, $first[1]);
    }

    public function test_works_for_every_byte_value(): void
    {
        $secret = implode('', array_map('chr', range(0, 255)));
        $shares = $this->shamir->split($secret, 4, 2);

        $this->assertSame($secret, $this->shamir->combine([2 => $shares[2], 4 => $shares[4]]));
    }

    public function test_two_of_two_and_ten_of_ten_work(): void
    {
        $secret = random_bytes(32);

        $this->assertSame($secret, $this->shamir->combine($this->shamir->split($secret, 2, 2)));
        $this->assertSame($secret, $this->shamir->combine($this->shamir->split($secret, 10, 10)));
    }

    public function test_invalid_parameters_are_refused(): void
    {
        foreach ([[5, 1], [3, 4], [11, 3], [1, 1]] as [$n, $k]) {
            try {
                $this->shamir->split(random_bytes(32), $n, $k);
                $this->fail("{$k} sur {$n} aurait du etre refuse.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_duplicate_shares_are_refused(): void
    {
        $shares = $this->shamir->split(random_bytes(32), 5, 3);

        $this->expectException(InvalidArgumentException::class);

        $this->shamir->combine([1 => $shares[1], 2 => $shares[2], 0 => $shares[1]]);
    }

    /**
     * @return list<list<int>>
     */
    private function combinations(int $n, int $k): array
    {
        if ($k === 0) {
            return [[]];
        }
        if ($n < $k) {
            return [];
        }

        $with = array_map(fn (array $c) => [...$c, $n], $this->combinations($n - 1, $k - 1));

        return [...$this->combinations($n - 1, $k), ...$with];
    }
}
