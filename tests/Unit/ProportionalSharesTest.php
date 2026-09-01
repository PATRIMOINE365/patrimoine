<?php

namespace Tests\Unit;

use App\Support\ProportionalShares;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * V1.0.48 (audit finding 4): shared amounts split by the
 * largest-remainder method — exact totals, no negative shares, and the
 * same inputs always produce the same split.
 */
class ProportionalSharesTest extends TestCase
{
    /**
     * The audit's own example: the old arithmetic produced [1, 1, 1, −1]
     * and MySQL's unsigned column refused the write.
     */
    public function test_the_audit_case_never_goes_negative(): void
    {
        $shares = ProportionalShares::allocate(2, [
            1 => 30,
            2 => 30,
            3 => 30,
            4 => 10,
        ]);

        $this->assertSame([1 => 1, 2 => 1, 3 => 0, 4 => 0], $shares);
    }

    public function test_shares_always_sum_to_the_amount(): void
    {
        foreach (
            [
                [0, [1 => 50, 2 => 50]],
                [1, [1 => 50, 2 => 50]],
                [100, [1 => 33.33, 2 => 33.33, 3 => 33.34]],
                [999, [1 => 12.5, 2 => 12.5, 3 => 75]],
                [7, [1 => 30, 2 => 30, 3 => 30, 4 => 10]],
                [1000000, [1 => 0.01, 2 => 99.99]],
            ] as [$amount, $percentages]
        ) {
            $shares = ProportionalShares::allocate($amount, $percentages);

            $this->assertSame(
                $amount,
                array_sum($shares),
                "Shares of {$amount} do not sum back."
            );

            foreach ($shares as $share) {
                $this->assertGreaterThanOrEqual(0, $share);
            }
        }
    }

    public function test_equal_remainders_break_ties_on_the_stable_key(): void
    {
        /*
         * 1 unit across four equal owners: the lowest ownership id
         * receives it, every time.
         */
        $first = ProportionalShares::allocate(1, [
            7 => 25,
            3 => 25,
            9 => 25,
            5 => 25,
        ]);

        $this->assertSame(1, $first[3]);
        $this->assertSame(0, $first[5] + $first[7] + $first[9]);

        /*
         * Determinism: rerunning changes nothing.
         */
        $this->assertSame(
            $first,
            ProportionalShares::allocate(1, [
                7 => 25,
                3 => 25,
                9 => 25,
                5 => 25,
            ])
        );
    }

    public function test_a_single_owner_takes_everything(): void
    {
        $this->assertSame(
            [42 => 12345],
            ProportionalShares::allocate(12345, [42 => 100])
        );
    }

    public function test_exact_percentages_split_exactly(): void
    {
        $this->assertSame(
            [1 => 6000, 2 => 4000],
            ProportionalShares::allocate(10000, [1 => 60, 2 => 40])
        );
    }

    public function test_decimal_string_percentages_are_read_exactly(): void
    {
        /*
         * The ownership column stores decimals as strings; two decimal
         * places must survive the trip into integer arithmetic.
         */
        $shares = ProportionalShares::allocate(10000, [
            1 => '33.33',
            2 => '33.33',
            3 => '33.34',
        ]);

        $this->assertSame(10000, array_sum($shares));
        $this->assertSame([1 => 3333, 2 => 3333, 3 => 3334], $shares);
    }

    public function test_negative_amounts_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProportionalShares::allocate(-1, [1 => 100]);
    }

    public function test_empty_participants_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProportionalShares::allocate(10, []);
    }
}
