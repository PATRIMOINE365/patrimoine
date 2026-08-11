<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles final settlement of a Lease's security deposit.
 *
 * Patrimoine business rules:
 * - Settlement uses the actual security-deposit ledger balance.
 * - Itemized deductions are summed from SecurityDepositDeduction records.
 * - Refund due = max(0, deposit balance - deductions).
 * - Tenant debt = max(0, deductions - deposit balance).
 * - A Lease may be settled only once.
 * - The settlement snapshot is preserved for audit/reporting purposes.
 */
class SecurityDepositService
{
    /**
     * Settle the security deposit for a Lease.
     */
    public function settle(
        Lease $lease,
        string $settlementDate,
        ?string $refundVoucherNumber = null,
        ?string $notes = null
    ): SecurityDepositSettlement {
        return DB::transaction(function () use (
            $lease,
            $settlementDate,
            $refundVoucherNumber,
            $notes
        ): SecurityDepositSettlement {
            $lease->refresh();

            /*
             * A final settlement must not be generated twice.
             */
            if ($lease->securityDepositSettlement()->exists()) {
                throw new RuntimeException(
                    'Security deposit has already been settled for this Lease.'
                );
            }

            /*
             * Locate the dedicated security-deposit fund account.
             */
            $account = TenantFundAccount::query()
                ->where('lease_id', $lease->id)
                ->where('type', 'security_deposit')
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                throw new RuntimeException(
                    'No security deposit account exists for this Lease.'
                );
            }

            $depositAmount = $account->balance();

            if ($depositAmount < 0) {
                throw new RuntimeException(
                    'Security deposit account has an invalid negative balance.'
                );
            }

            /*
             * Sum all itemized deductions assessed against the Lease.
             */
            $deductionAmount = (int) $lease
                ->securityDepositDeductions()
                ->sum('amount');

            $refundAmount = max(
                0,
                $depositAmount - $deductionAmount
            );

            $tenantDebtAmount = max(
                0,
                $deductionAmount - $depositAmount
            );

            /*
             * Consume the portion of the deposit that is retained
             * against deductions.
             *
             * If deductions exceed the deposit, only the available
             * deposit balance is debited. The excess becomes tenant debt.
             */
            $depositUsedForDeductions = min(
                $depositAmount,
                $deductionAmount
            );

            if ($depositUsedForDeductions > 0) {
                TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,
                    'direction' => 'debit',
                    'category' => 'deposit_deduction',
                    'amount' => $depositUsedForDeductions,
                    'transaction_date' => $settlementDate,
                    'notes' => 'Security deposit deductions applied during final settlement.',
                ]);
            }

            /*
             * Any remaining balance is refunded to the tenant and recorded
             * as a separate ledger debit.
             */
            if ($refundAmount > 0) {
                TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,
                    'direction' => 'debit',
                    'category' => 'refund',
                    'amount' => $refundAmount,
                    'transaction_date' => $settlementDate,
                    'reference' => $refundVoucherNumber,
                    'notes' => 'Security deposit refund issued during final settlement.',
                ]);
            }

            /*
             * Record the immutable settlement snapshot after all
             * calculations are complete.
             */
            return SecurityDepositSettlement::create([
                'lease_id' => $lease->id,
                'deposit_amount' => $depositAmount,
                'deduction_amount' => $deductionAmount,
                'refund_amount' => $refundAmount,
                'tenant_debt_amount' => $tenantDebtAmount,
                'settlement_date' => $settlementDate,
                'refund_voucher_number' => $refundVoucherNumber,
                'notes' => $notes,
            ]);
        });
    }
}
