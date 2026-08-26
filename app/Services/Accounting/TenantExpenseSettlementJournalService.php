<?php

namespace App\Services\Accounting;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use RuntimeException;

/**
 * Posts the Financial Journal entry for an expense Invoice settled from
 * a tenant fund account.
 *
 * No cash moves at settlement time: the tenant's held funds absorb an
 * expense already recognized in the Property Expense Clearing account,
 * exactly mirroring the owner-expense treatment. The debit side varies
 * with the fund account type.
 *
 * Before the controlled accounting cutover this posting is a no-op.
 */
class TenantExpenseSettlementJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting,
    ) {}

    public function post(
        TenantFundTransaction $transaction
    ): void {
        if (! $this->runtimeGate->enabled()) {
            return;
        }

        $transaction->loadMissing(
            'account'
        );

        $account =
            $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Tenant expense settlement has no valid Tenant fund account.'
            );
        }

        if (
            $transaction->direction !== 'debit'
            || $transaction->category !== 'expense_settlement'
        ) {
            throw new RuntimeException(
                'Invalid Tenant expense settlement transaction.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Tenant expense settlement Journal amount must be greater than zero.'
            );
        }

        $mapping =
            $this->eventMap->contextual(
                AccountingEventMap::EVENT_TENANT_EXPENSE_SETTLEMENT
            );

        if (
            ($mapping['variable'] ?? null)
                !== 'tenant_fund_liability'
            || ($mapping['credit'] ?? null)
                !== SystemChartOfAccounts::PROPERTY_EXPENSE_CLEARING
        ) {
            throw new RuntimeException(
                'Tenant expense settlement accounting mapping is inconsistent.'
            );
        }

        $liabilityAccount =
            $this->eventMap
                ->tenantFundLiability(
                    $account->type
                );

        $this->journalPosting->post(
            [
                'journal_date' => $transaction
                    ->transaction_date
                    ->toDateString(),

                'transaction_type' => AccountingEventMap::EVENT_TENANT_EXPENSE_SETTLEMENT,

                'description' => __(
                    'financial_journal.descriptions.expense_invoice_settlement',
                    ['reference' => $transaction->id]
                ),

                'source_type' => TenantFundTransaction::class,

                'source_id' => $transaction->id,

                'idempotency_key' => self::idempotencyKey(
                    $transaction
                ),

                'snapshot' => [
                    'tenant_fund_transaction_id' => $transaction->id,

                    'tenant_fund_account_id' => $account->id,

                    'lease_id' => $account->lease_id,

                    'fund_type' => $account->type,

                    'invoice_id' => $transaction->invoice_id,

                    'amount' => $amount,

                    'reference' => $transaction->reference,
                ],
            ],
            [
                [
                    'account_code' => $liabilityAccount,

                    'debit' => $amount,

                    'credit' => 0,

                    'memo' => 'Reduce Tenant funds held',
                ],
                [
                    'account_code' => SystemChartOfAccounts::PROPERTY_EXPENSE_CLEARING,

                    'debit' => 0,

                    'credit' => $amount,

                    'memo' => 'Settle expense Invoice from held funds',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'tenant-expense-settlement:'
            .$transaction->id;
    }
}
