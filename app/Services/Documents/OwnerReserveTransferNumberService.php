<?php

namespace App\Services\Documents;

/**
 * OTR-YYYY-NNNNNN.
 *
 * V1.0.36: from the shared counter, and the year is now part of the
 * number. It used to be found by reading the highest OTR- reference
 * already written, which meant deleting the newest transfer handed its
 * number to the next one.
 */
class OwnerReserveTransferNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    public function next(): string
    {
        return $this->numbers->next('OTR');
    }
}
