<?php

namespace App\Services\Accounting;

use App\Models\AccountingCutover;

/**
 * Controls whether operational V1.0.5 Financial Journal posting is active.
 *
 * Runtime accounting becomes active only after the controlled opening-balance
 * cutover has completed successfully. This prevents pre-cutover operational
 * transactions from being mixed with the historical opening position.
 *
 * This gate is deliberately read-only. It must never initialize or perform
 * the accounting cutover itself.
 */
class AccountingRuntimeGate
{
    public function enabled(): bool
    {
        return AccountingCutover::query()
            ->where(
                'cutover_key',
                AccountingCutover::V105_OPENING_BALANCE
            )
            ->where(
                'status',
                AccountingCutover::STATUS_COMPLETED
            )
            ->whereNotNull('completed_at')
            ->exists();
    }
}
