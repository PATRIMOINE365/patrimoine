<?php

namespace App\Services\Adjustments;

/**
 * Adapter between the universal Adjustment workflow and an existing
 * operational balance-maintaining account.
 *
 * Implementations own the account-specific locking, balance read and
 * operational transaction creation rules.
 */
interface AdjustmentAccountAdapter
{
    public function supports(AdjustmentContext $context): bool;

    public function currentBalance(AdjustmentContext $context): int;

    public function apply(
        AdjustmentCommand $command,
        AdjustmentCalculation $calculation,
    ): AdjustmentResult;
}
