<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Split a whole-currency amount across owners by percentage without
 * ever inventing, losing, or going below zero on anybody's share —
 * the largest-remainder method, in integer arithmetic throughout.
 *
 * The method it replaces rounded each share independently and handed
 * the LAST owner whatever remained, which could be negative: 2 split
 * 30/30/30/10 rounded to 1+1+1, leaving the last owner −1 — an amount
 * the ledger's unsigned column rightly refuses (audit finding 4).
 *
 * Here every owner gets the floor of their exact share first; the few
 * remaining units — always fewer than the number of owners — go one
 * each to the largest fractional remainders, ties broken on the stable
 * key ascending so the same inputs always produce the same split.
 * 2 split 30/30/30/10 becomes [1, 1, 0, 0]: exact total, no negatives,
 * deterministic.
 *
 * Percentages are read to four decimal places, which is finer than the
 * two the ownership column stores, so nothing the application can
 * record is truncated here.
 */
class ProportionalShares
{
    /**
     * Scale for percentage arithmetic: 100% = 1,000,000 parts-per.
     */
    private const SCALE = 10000;

    /**
     * @param  int  $amount  Whole-currency amount to split; non-negative.
     * @param  array<int|string, float|int|string>  $percentages
     *         Percentage per stable key (ownership id). Must sum to 100
     *         within the tolerance the caller has already enforced.
     * @return array<int|string, int> Share per key, same keys, summing
     *         exactly to $amount, every share >= 0.
     */
    public static function allocate(
        int $amount,
        array $percentages
    ): array {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Only non-negative amounts can be shared.'
            );
        }

        if ($percentages === []) {
            throw new InvalidArgumentException(
                'At least one participant is required.'
            );
        }

        /*
         * Percentages arrive as decimals ("33.33"). Scaling by 10^4
         * turns them into integers exactly, so everything after this
         * line is integer arithmetic with no float drift.
         */
        $scaled = [];

        foreach ($percentages as $key => $percentage) {
            $parts = (int) round(
                ((float) $percentage) * self::SCALE
            );

            if ($parts < 0) {
                throw new InvalidArgumentException(
                    'Ownership percentages cannot be negative.'
                );
            }

            $scaled[$key] = $parts;
        }

        $totalParts = array_sum($scaled);

        if ($totalParts <= 0) {
            throw new InvalidArgumentException(
                'Ownership percentages must sum above zero.'
            );
        }

        /*
         * Floor of the exact share, and the remainder that decides who
         * receives the leftover units.
         */
        $shares = [];
        $remainders = [];
        $assigned = 0;

        foreach ($scaled as $key => $parts) {
            $exact = $amount * $parts;

            $shares[$key] = intdiv($exact, $totalParts);
            $remainders[$key] = $exact % $totalParts;

            $assigned += $shares[$key];
        }

        $leftover = $amount - $assigned;

        /*
         * Hand the remaining units to the largest remainders, one each.
         * Ties break on the key ascending — the stable ownership id —
         * so a re-run distributes identically.
         */
        if ($leftover > 0) {
            $order = array_keys($remainders);

            usort(
                $order,
                function ($a, $b) use ($remainders): int {
                    if ($remainders[$a] !== $remainders[$b]) {
                        return $remainders[$b] <=> $remainders[$a];
                    }

                    return $a <=> $b;
                }
            );

            foreach ($order as $key) {
                if ($leftover <= 0) {
                    break;
                }

                $shares[$key]++;
                $leftover--;
            }
        }

        return $shares;
    }
}
