<?php

namespace App\Services\Documents;

use App\Models\WithdrawalReceipt;
use Illuminate\Support\Facades\DB;

class WithdrawalReceiptNumberService
{
    public function next(): string
    {
        return DB::transaction(
            function (): string {
                $year =
                    now()->year;

                $prefix =
                    sprintf(
                        'WDR-%d-',
                        $year
                    );

                $last =
                    WithdrawalReceipt::query()
                        ->where(
                            'receipt_number',
                            'like',
                            $prefix.'%'
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'receipt_number'
                        )
                        ->value(
                            'receipt_number'
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
                    'WDR-%d-%06d',
                    $year,
                    $sequence
                );
            }
        );
    }
}
