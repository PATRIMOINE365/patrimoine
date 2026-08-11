<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles controlled consumption of a Lease's Rent Reserve.
 *
 * Patrimoine business rules:
 * - Only a rent_reserve account may be consumed by this service.
 * - The Lease must be in termination notice.
 * - A termination_notice_date must exist.
 * - Reserve consumption cannot exceed the available balance.
 * - Every consumption is recorded as an auditable ledger transaction.
 */
class RentReserveService
{
    /**
     * Consume Rent Reserve to settle all or part of an Invoice.
     */
    public function consume(
        TenantFundAccount $account,
        Invoice $invoice,
        int $amount,
        string $transactionDate
    ): TenantFundTransaction {
        return DB::transaction(function () use (
            $account,
            $invoice,
            $amount,
            $transactionDate
        ): TenantFundTransaction {
            $account->refresh();

            if ($account->type !== 'rent_reserve') {
                throw new RuntimeException(
                    'Only a Rent Reserve account can be consumed by this service.'
                );
            }

            $lease = $account->lease;

            /*
             * Reserve funds remain protected until formal termination
             * notice has been recorded on the Lease.
             */
            if (
                $lease->status !== 'notice'
                || $lease->termination_notice_date === null
            ) {
                throw new RuntimeException(
                    'Rent Reserve cannot be consumed before termination notice.'
                );
            }

            /*
             * Prevent accidental use against an Invoice belonging to a
             * different Lease.
             */
            if ($invoice->lease_id !== $lease->id) {
                throw new RuntimeException(
                    'The Invoice does not belong to the Rent Reserve Lease.'
                );
            }

            if ($amount <= 0) {
                throw new RuntimeException(
                    'Rent Reserve consumption amount must be greater than zero.'
                );
            }

            if ($amount > $account->balance()) {
                throw new RuntimeException(
                    'Rent Reserve balance is insufficient.'
                );
            }

            if ($amount > $invoice->outstandingAmount()) {
                throw new RuntimeException(
                    'Rent Reserve consumption exceeds the Invoice outstanding amount.'
                );
            }

            return TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'invoice_id' => $invoice->id,
                'direction' => 'debit',
                'category' => 'rent_consumption',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
            ]);
        });
    }
}
