<?php

namespace App\Services\Documents;

use App\Models\OwnerTransaction;

/**
 * V1.0.8: sequential OTR- numbers for owner reserve transfers.
 *
 * The generated number doubles as the operational reference stored on
 * the reserve_transfer ledger row and printed on its voucher.
 */
class OwnerReserveTransferNumberService
{
    public function next(): string
    {
        $prefix = 'OTR-';

        /*
         * Lock the latest transfer row during generation so two
         * concurrent transfers cannot receive the same number.
         */
        $last = OwnerTransaction::query()
            ->where('category', 'reserve_transfer')
            ->where('reference', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('reference');

        $next = 1;

        if (is_string($last)
            && preg_match('/^OTR-(\d+)$/', $last, $matches)
        ) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }
}
