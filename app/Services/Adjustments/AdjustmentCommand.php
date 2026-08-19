<?php

namespace App\Services\Adjustments;

use App\Models\User;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Immutable request to perform a contextual Adjustment.
 *
 * V1.0.5 semantics:
 * - caller supplies the desired final balance;
 * - reason is mandatory;
 * - effective date is always today;
 * - no backdating field exists here.
 */
final readonly class AdjustmentCommand
{
    public CarbonImmutable $effectiveDate;

    public function __construct(
        public AdjustmentContext $context,
        public int $previousBalance,
        public int $correctedBalance,
        public string $reason,
        public User $performedBy,
    ) {
        $reason = trim($this->reason);

        if ($reason === '') {
            throw new InvalidArgumentException(
                'An adjustment reason is required.'
            );
        }

        $this->effectiveDate = CarbonImmutable::today();
    }

    public function calculation(): AdjustmentCalculation
    {
        return AdjustmentCalculation::make(
            accountType: $this->context->accountType,
            previousBalance: $this->previousBalance,
            correctedBalance: $this->correctedBalance,
        );
    }
}
