<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles final settlement of a Lease's Security Deposit.
 *
 * Patrimoine business rules:
 *
 * - settlement uses the actual Security Deposit ledger balance;
 * - itemized deductions are summed from SecurityDepositDeduction records;
 * - refund due = max(0, deposit balance - deductions);
 * - tenant debt = max(0, deductions - deposit balance);
 * - a Lease may be settled only once;
 * - Patrimoine generates the settlement voucher number;
 * - the settlement snapshot is preserved for audit/reporting purposes.
 */
class SecurityDepositService
{
    /**
     * Settle the Security Deposit for a Lease.
     */
    public function settle(
        Lease $lease,
        string $settlementDate,
        ?string $notes = null
    ): SecurityDepositSettlement {
        return DB::transaction(
            function () use (
                $lease,
                $settlementDate,
                $notes
            ): SecurityDepositSettlement {
                $lease->refresh();

                /*
                 * A final settlement must never be generated twice.
                 */
                if (
                    $lease
                        ->securityDepositSettlement()
                        ->exists()
                ) {
                    throw new RuntimeException(
                        'Security deposit has already been settled for this Lease.'
                    );
                }

                /*
                 * Locate and lock the dedicated Security Deposit account.
                 *
                 * Settlement changes its financial balance, therefore
                 * concurrent close-out attempts must not use the same funds.
                 */
                $account =
                    TenantFundAccount::query()
                        ->where(
                            'lease_id',
                            $lease->id
                        )
                        ->where(
                            'type',
                            'security_deposit'
                        )
                        ->lockForUpdate()
                        ->first();

                if ($account === null) {
                    throw new RuntimeException(
                        'No security deposit account exists for this Lease.'
                    );
                }

                $depositAmount =
                    $account->balance();

                if ($depositAmount < 0) {
                    throw new RuntimeException(
                        'Security deposit account has an invalid negative balance.'
                    );
                }

                /*
                 * Calculate deductions from the recorded itemized close-out
                 * charges. These records remain available for the formal
                 * settlement voucher.
                 */
                $deductionAmount =
                    (int) $lease
                        ->securityDepositDeductions()
                        ->sum(
                            'amount'
                        );

                $refundAmount =
                    max(
                        0,
                        $depositAmount
                        - $deductionAmount
                    );

                $tenantDebtAmount =
                    max(
                        0,
                        $deductionAmount
                        - $depositAmount
                    );

                /*
                 * Create the immutable settlement snapshot first.
                 *
                 * The database ID provides a concurrency-safe source for the
                 * customer-facing voucher number without maintaining a second
                 * numbering counter.
                 */
                $settlement =
                    SecurityDepositSettlement::create([
                        'lease_id' =>
                            $lease->id,

                        'deposit_amount' =>
                            $depositAmount,

                        'deduction_amount' =>
                            $deductionAmount,

                        'refund_amount' =>
                            $refundAmount,

                        'tenant_debt_amount' =>
                            $tenantDebtAmount,

                        'settlement_date' =>
                            $settlementDate,

                        'refund_voucher_number' =>
                            null,

                        'notes' =>
                            $notes,
                    ]);

                $voucherNumber =
                    sprintf(
                        'SDV-%06d',
                        $settlement->id
                    );

                $settlement->update([
                    'refund_voucher_number' =>
                        $voucherNumber,
                ]);

                /*
                 * Consume the portion of the deposit retained against
                 * deductions.
                 *
                 * If deductions exceed the available deposit, only the held
                 * balance is consumed. The excess remains represented by the
                 * settlement's tenant_debt_amount.
                 */
                $depositUsedForDeductions =
                    min(
                        $depositAmount,
                        $deductionAmount
                    );

                if (
                    $depositUsedForDeductions
                    > 0
                ) {
                    TenantFundTransaction::create([
                        'tenant_fund_account_id' =>
                            $account->id,

                        'direction' =>
                            'debit',

                        'category' =>
                            'deposit_deduction',

                        'amount' =>
                            $depositUsedForDeductions,

                        'transaction_date' =>
                            $settlementDate,

                        'reference' =>
                            $voucherNumber,

                        'notes' =>
                            'Security deposit deductions applied during final settlement.',
                    ]);
                }

                /*
                 * Any remaining held balance is refunded to the tenant.
                 *
                 * The generated voucher number is also recorded on the fund
                 * transaction so accounting and document histories reconcile.
                 */
                if ($refundAmount > 0) {
                    TenantFundTransaction::create([
                        'tenant_fund_account_id' =>
                            $account->id,

                        'direction' =>
                            'debit',

                        'category' =>
                            'refund',

                        'amount' =>
                            $refundAmount,

                        'transaction_date' =>
                            $settlementDate,

                        'reference' =>
                            $voucherNumber,

                        'notes' =>
                            'Security deposit refund issued during final settlement.',
                    ]);
                }

                return $settlement
                    ->refresh();
            }
        );
    }
}
