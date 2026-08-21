<?php

namespace App\Services\Documents;

use App\Models\AdjustmentVoucher;

class AdjustmentVoucherNumberService
{
    public function next(): string
    {
        /*
         * Adjustment Voucher numbering intentionally remains separate
         * from Journal numbering and from every other financial
         * document numbering scheme.
         *
         * Lock the relevant voucher rows during generation so two
         * concurrent adjustments cannot receive the same number.
         */
        $year = now()->year;
        $prefix = 'ADV-'.$year.'-';

        $last = AdjustmentVoucher::query()
            ->where('voucher_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('voucher_number');

        $next = 1;

        if (is_string($last)
            && preg_match('/^ADV-\d{4}-(\d+)$/', $last, $matches)
        ) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }
}
