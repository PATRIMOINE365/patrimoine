<?php

namespace App\Services\Adjustments;

use InvalidArgumentException;

/**
 * Immutable calculation describing one requested balance correction.
 *
 * Amounts are integer minor/business units because Patrimoine's
 * established financial model uses whole-number amounts.
 */
final readonly class AdjustmentCalculation
{
    public function __construct(
        public string $accountType,
        public int $previousBalance,
        public int $correctedBalance,
        public int $difference,
        public string $direction,
        public bool $allowsNegativeBalance,
    ) {
    }

    public static function make(
        string $accountType,
        int $previousBalance,
        int $correctedBalance,
    ): self {
        if (! in_array(
            $accountType,
            AdjustmentAccountType::all(),
            true
        )) {
            throw new InvalidArgumentException(
                'Unsupported adjustment account type.'
            );
        }

        $allowsNegativeBalance =
            AdjustmentAccountType::allowsNegativeBalance($accountType);

        if ($correctedBalance < 0 && ! $allowsNegativeBalance) {
            throw new InvalidArgumentException(
                'The selected account cannot have a negative balance.'
            );
        }

        $difference = $correctedBalance - $previousBalance;

        $direction = match (true) {
            $difference > 0 => 'increase',
            $difference < 0 => 'decrease',
            default => 'none',
        };

        return new self(
            accountType: $accountType,
            previousBalance: $previousBalance,
            correctedBalance: $correctedBalance,
            difference: $difference,
            direction: $direction,
            allowsNegativeBalance: $allowsNegativeBalance,
        );
    }

    public function absoluteDifference(): int
    {
        return abs($this->difference);
    }

    public function changesBalance(): bool
    {
        return $this->difference !== 0;
    }
}
