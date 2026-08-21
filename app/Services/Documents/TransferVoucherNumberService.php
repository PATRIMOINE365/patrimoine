<?php

namespace App\Services\Documents;

use App\Models\TenantFundTransaction;

class TransferVoucherNumberService
{
    public function next(): string
    {
        /*
         * Transfer voucher numbering intentionally remains separate
         * from Journal numbering and from every other financial
         * document numbering scheme.
         *
         * The generated number doubles as the shared operational
         * reference stored on both legs of one Tenant fund Transfer.
         *
         * Lock the relevant transfer rows during generation so two
         * concurrent transfers cannot receive the same number.
         */
        $prefix = 'TRF-';

        $last = TenantFundTransaction::query()
            ->where('category', 'transfer')
            ->where('direction', 'debit')
            ->where('reference', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('reference');

        $next = 1;

        if (is_string($last)
            && preg_match('/^TRF-(\d+)$/', $last, $matches)
        ) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }
}
