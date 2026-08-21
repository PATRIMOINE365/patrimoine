<?php

namespace App\Services\Adjustments;

use Carbon\CarbonImmutable;

/**
 * Stable result returned by an Adjustment account adapter.
 *
 * Step 2 establishes this contract only. Later Phase 4 steps will
 * provide Owner/Tenant adapters and persist voucher/activity/journal
 * information atomically.
 */
final readonly class AdjustmentResult
{
    /**
     * @param array<string, mixed> $transactionSnapshot
     */
    public function __construct(
        public AdjustmentContext $context,
        public AdjustmentCalculation $calculation,
        public CarbonImmutable $effectiveDate,
        public string $reason,
        public int $performedByUserId,
        public string $transactionType,
        public int $transactionId,
        public array $transactionSnapshot = [],
    ) {
    }
}
