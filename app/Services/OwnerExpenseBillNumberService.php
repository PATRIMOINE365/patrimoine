<?php

namespace App\Services;

use App\Models\OwnerExpenseBill;
use Illuminate\Support\Facades\DB;

/**
 * Issues sequential owner expense bill numbers.
 *
 * Format: OEB-000001
 *
 * Modeled on WithdrawalReceiptNumberService: the highest existing number
 * is read under a row lock inside a transaction so concurrent billing
 * requests can never issue the same bill number.
 */
class OwnerExpenseBillNumberService
{
    public function next(): string
    {
        return DB::transaction(
            function (): string {
                $prefix =
                    'OEB-';

                $last =
                    OwnerExpenseBill::query()
                        ->where(
                            'bill_number',
                            'like',
                            $prefix.'%'
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'bill_number'
                        )
                        ->value(
                            'bill_number'
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
                    'OEB-%06d',
                    $sequence
                );
            }
        );
    }
}
