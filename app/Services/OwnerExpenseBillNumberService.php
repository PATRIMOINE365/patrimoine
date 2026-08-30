<?php

namespace App\Services;

use App\Services\Documents\DocumentNumberService;

/**
 * OEB-YYYY-NNNNNN.
 *
 * V1.0.36: from the shared counter, and the year is now part of the
 * number.
 */
class OwnerExpenseBillNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    public function next(): string
    {
        return $this->numbers->next('OEB');
    }
}
