<?php

namespace App\Services\Adjustments;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Reusable V1.0.5 Adjustment orchestration foundation.
 *
 * Responsibilities:
 * - resolve the operational account adapter;
 * - obtain the authoritative current balance;
 * - apply correct-balance semantics;
 * - enforce the account negative-balance policy;
 * - require a non-empty reason;
 * - force today's effective date through AdjustmentCommand;
 * - provide one database transaction boundary for the eventual
 *   operational transaction + voucher metadata + Activity Log +
 *   Financial Journal posting.
 *
 * Owner and Tenant conversion intentionally occurs in later Phase 4
 * steps.
 */
final class ContextualAdjustmentService
{
    /**
     * @param iterable<AdjustmentAccountAdapter> $adapters
     */
    public function __construct(
        private readonly iterable $adapters = [],
    ) {
    }

    public function calculate(
        AdjustmentContext $context,
        int $currentBalance,
        int $correctedBalance,
    ): AdjustmentCalculation {
        return AdjustmentCalculation::make(
            accountType: $context->accountType,
            previousBalance: $currentBalance,
            correctedBalance: $correctedBalance,
        );
    }

    public function perform(
        AdjustmentContext $context,
        int $correctedBalance,
        string $reason,
        \App\Models\User $performedBy,
    ): AdjustmentResult {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException(
                'An adjustment reason is required.'
            );
        }

        return DB::transaction(function () use (
            $context,
            $correctedBalance,
            $reason,
            $performedBy,
        ): AdjustmentResult {
            $adapter = $this->resolveAdapter($context);

            /*
             * The adapter is responsible for obtaining its balance in
             * the account-specific locking context required to prevent
             * stale corrections.
             */
            $currentBalance = $adapter->currentBalance($context);

            $command = new AdjustmentCommand(
                context: $context,
                previousBalance: $currentBalance,
                correctedBalance: $correctedBalance,
                reason: $reason,
                performedBy: $performedBy,
            );

            $calculation = $command->calculation();

            return $adapter->apply(
                command: $command,
                calculation: $calculation,
            );
        });
    }

    private function resolveAdapter(
        AdjustmentContext $context
    ): AdjustmentAccountAdapter {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($context)) {
                return $adapter;
            }
        }

        throw new RuntimeException(
            'No Adjustment account adapter supports this context.'
        );
    }
}
