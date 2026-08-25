<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Issues sequential expense Invoice numbers.
 *
 * Format: EXP-000001
 *
 * Expense invoices deliberately number in their own series, separate
 * from rent invoices. Modeled on OwnerExpenseBillNumberService: the
 * highest existing number is read under a row lock inside a
 * transaction so concurrent recordings can never issue the same
 * number.
 */
class ExpenseInvoiceNumberService
{
    public function next(): string
    {
        return DB::transaction(
            function (): string {
                $prefix =
                    'EXP-';

                $last =
                    Invoice::query()
                        ->where(
                            'invoice_number',
                            'like',
                            $prefix.'%'
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'invoice_number'
                        )
                        ->value(
                            'invoice_number'
                        );

                $sequence =
                    $last === null
                        ? 1
                        : (
                            (int) substr(
                                $last,
                                -6
                            )
                        ) + 1;

                return sprintf(
                    'EXP-%06d',
                    $sequence
                );
            }
        );
    }
}
