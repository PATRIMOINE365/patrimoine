<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Models\Invoice;

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
                        __('business.security_deposit.already_settled')
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
                        __('business.security_deposit.account_missing')
                    );
                }

                $depositAmount =
                    $account->balance();

                if ($depositAmount < 0) {
                    throw new RuntimeException(
                        __('business.security_deposit.negative_balance')
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
 * Excess Security Deposit deductions remain a genuine amount owed by
 * the tenant after the held deposit has been exhausted.
 *
 * Represent that amount as an Invoice so Patrimoine can use its existing
 * Payment and PaymentAllocation infrastructure for collection.
 *
 * This is deliberately NOT a rent Invoice. Cash later collected against
 * this receivable must not create owner rent entitlement or management
 * fees.
 */
if ($tenantDebtAmount > 0) {
    $debtInvoice =
        Invoice::create([
            'lease_id' =>
                $lease->id,

            'invoice_number' =>
                sprintf(
                    'SDD-%06d',
                    $settlement->id
                ),

            'type' =>
                'security_deposit_debt',

            /*
             * A close-out debt has no recurring rent billing period.
             *
             * The settlement date is used for both dates so the existing
             * Invoice schema remains auditable without inventing a rent
             * period.
             */
            'period_start' =>
                $settlementDate,

            'period_end' =>
                $settlementDate,

            'issue_date' =>
                $settlementDate,

            'due_date' =>
                $settlementDate,

            'status' =>
                'issued',

            'total_amount' =>
                $tenantDebtAmount,

            /*
             * This is recovery of a Security Deposit close-out charge,
             * not contractual VAT-inclusive rent.
             */
            'vat_rate' =>
                0,

            'net_amount' =>
                $tenantDebtAmount,

            'vat_amount' =>
                0,

            'proration_amount' =>
                null,

            'notes' =>
                sprintf(
                    'Security Deposit close-out debt from settlement %s.',
                    $voucherNumber
                ),
        ]);

    $settlement->update([
        'debt_invoice_id' =>
            $debtInvoice->id,
    ]);
}



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
