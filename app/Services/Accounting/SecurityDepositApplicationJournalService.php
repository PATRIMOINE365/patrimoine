<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\SecurityDepositApplication;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Posts manual Security Deposit applications to the immutable Journal.
 *
 * No cash asset moves. Held Security Deposit liability is released directly
 * against the receivable being settled.
 */
class SecurityDepositApplicationJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly JournalPostingService $journalPosting
    ) {}

    public function post(
        SecurityDepositApplication $application,
        TenantFundTransaction $transaction,
        Invoice $invoice
    ): ?JournalEntry {
        if (! $this->runtimeGate->enabled()) {
            return null;
        }

        $transaction->loadMissing('account');

        $account = $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Security Deposit application has no valid fund account.'
            );
        }

        if (
            $account->type !== 'security_deposit'
            || $transaction->direction !== 'debit'
            || $transaction->category !== 'deposit_deduction'
        ) {
            throw new RuntimeException(
                'Invalid Security Deposit application transaction.'
            );
        }

        if (
            (int) $invoice->lease_id
            !== (int) $application->lease_id
            || (int) $account->lease_id
            !== (int) $application->lease_id
        ) {
            throw new RuntimeException(
                'Security Deposit application Lease context is inconsistent.'
            );
        }

        $amount = (int) $application->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Security Deposit application Journal amount must be greater than zero.'
            );
        }

        $creditAccount = match ($invoice->type) {
            'rent' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

            'security_deposit_debt' => SystemChartOfAccounts::SECURITY_DEPOSIT_DEBT_RECEIVABLE,

            default => throw new RuntimeException(
                'The selected Invoice type cannot be settled using Security Deposit.'
            ),
        };

        return $this->journalPosting->post(
            [
                'journal_date' => Carbon::parse(
                    $application->application_date
                )->toDateString(),

                'transaction_type' => AccountingEventMap::EVENT_SECURITY_DEPOSIT_APPLIED,

                'description' => 'Security Deposit applied to '
                    .$invoice->invoice_number,

                'source_type' => SecurityDepositApplication::class,

                'source_id' => (int) $application->id,

                'idempotency_key' => 'manual-security-deposit-application:'
                    .$application->id,

                'snapshot' => [
                    'security_deposit_application_id' => (int) $application->id,

                    'tenant_fund_transaction_id' => (int) $transaction->id,

                    'tenant_fund_account_id' => (int) $account->id,

                    'lease_id' => (int) $application->lease_id,

                    'invoice_id' => (int) $invoice->id,

                    'invoice_number' => $invoice->invoice_number,

                    'invoice_type' => $invoice->type,

                    'amount' => $amount,
                ],
            ],
            [
                [
                    'account_code' => SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

                    'debit' => $amount,

                    'credit' => 0,

                    'memo' => 'Release Security Deposit liability',
                ],
                [
                    'account_code' => $creditAccount,

                    'debit' => 0,

                    'credit' => $amount,

                    'memo' => 'Settle Lease receivable using Security Deposit',
                ],
            ]
        );
    }
}
