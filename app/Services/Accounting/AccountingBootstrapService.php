<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\Schema;

/**
 * Ensures Patrimoine's internal accounting foundation is ready.
 *
 * Safe to call repeatedly.
 */
class AccountingBootstrapService
{
    public function __construct(
        private readonly SystemChartOfAccounts $chart
    ) {
    }

    public function bootstrap(): void
    {
        if (! Schema::hasTable('accounting_accounts')) {
            return;
        }

        $this->chart->install();
    }
}
