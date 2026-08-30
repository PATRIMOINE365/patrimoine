<?php

namespace App\Services\Documents;

/**
 * ADV-YYYY-NNNNNN.
 *
 * V1.0.36: the number comes from the shared counter rather than from the
 * highest voucher already written, so deleting the newest one can no
 * longer hand its number to the next. The shape a customer reads is
 * unchanged.
 */
class AdjustmentVoucherNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    public function next(): string
    {
        return $this->numbers->next('ADV');
    }
}
