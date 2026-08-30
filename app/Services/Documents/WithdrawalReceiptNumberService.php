<?php

namespace App\Services\Documents;

/**
 * WDR-YYYY-NNNNNN.
 *
 * V1.0.36: from the shared counter. The shape a customer reads is
 * unchanged — it already carried its year — but the number no longer
 * comes from a text sort of the receipts already issued, which was only
 * ever right while every one of them was exactly six digits wide.
 */
class WithdrawalReceiptNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    public function next(): string
    {
        return $this->numbers->next('WDR');
    }
}
