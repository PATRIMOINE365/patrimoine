<?php

namespace App\Services;

use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\SecurityDepositDeduction;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundTransaction;

/**
 * Builds meaningful Activity Log context for manual financial actions.
 *
 * Activity Log is intentionally not a financial ledger. These snapshots
 * identify the explicit human command and its principal business record
 * without reproducing every automatic accounting consequence.
 */
class FinancialActivitySnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function payment(Payment $payment): array
    {
        return [
            'lease_id' => $payment->lease_id,
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date?->toDateString(),
            'payment_method' => $payment->payment_method,
            'reference' => $payment->reference,
            'collector_name' => $payment->collector_name,
            'notes' => $payment->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tenantFundTransaction(
        TenantFundTransaction $transaction
    ): array {
        return [
            'tenant_fund_account_id' => $transaction->tenant_fund_account_id,
            'payment_id' => $transaction->payment_id,
            'invoice_id' => $transaction->invoice_id,
            'direction' => $transaction->direction,
            'category' => $transaction->category,
            'amount' => $transaction->amount,
            'transaction_date' => $transaction->transaction_date?->toDateString(),
            'reference' => $transaction->reference,
            'notes' => $transaction->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function securityDepositDeduction(
        SecurityDepositDeduction $deduction
    ): array {
        return [
            'lease_id' => $deduction->lease_id,
            'description' => $deduction->description,
            'amount' => $deduction->amount,
            'deduction_date' => $deduction->deduction_date?->toDateString(),
            'reference' => $deduction->reference,
            'notes' => $deduction->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function securityDepositSettlement(
        SecurityDepositSettlement $settlement
    ): array {
        return [
            'lease_id' => $settlement->lease_id,
            'settlement_date' => $settlement->settlement_date?->toDateString(),
            'deposit_amount' => $settlement->deposit_amount,
            'deduction_amount' => $settlement->deduction_amount,
            'refund_amount' => $settlement->refund_amount,
            'tenant_debt_amount' => $settlement->tenant_debt_amount,
            'notes' => $settlement->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ownerTransaction(
        OwnerTransaction $transaction
    ): array {
        return collect($transaction->getAttributes())
            ->except([
                'created_at',
                'updated_at',
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function ownerExpense(
        OwnerExpense $expense
    ): array {
        return collect($expense->getAttributes())
            ->except([
                'created_at',
                'updated_at',
            ])
            ->all();
    }
}
