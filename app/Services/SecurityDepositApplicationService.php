<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\SecurityDepositApplication;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Services\Accounting\SecurityDepositApplicationJournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Manually applies held Security Deposit to an outstanding Lease receivable.
 *
 * This is not a Payment and not a Withdrawal. No external asset moves.
 */
class SecurityDepositApplicationService
{
    public function __construct(
        private readonly SecurityDepositApplicationJournalService $journal
    ) {}

    public function apply(
        Lease $lease,
        Invoice $invoice,
        int $amount,
        string $transactionDate,
        ?string $notes = null
    ): SecurityDepositApplication {
        return DB::transaction(
            function () use (
                $lease,
                $invoice,
                $amount,
                $transactionDate,
                $notes
            ): SecurityDepositApplication {
                if ($amount <= 0) {
                    throw new RuntimeException(
                        'Security Deposit application amount must be greater than zero.'
                    );
                }

                $lease = Lease::query()
                    ->whereKey($lease->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $invoice = Invoice::query()
                    ->whereKey($invoice->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $invoice->lease_id
                    !== (int) $lease->id
                ) {
                    throw new RuntimeException(
                        'The selected receivable does not belong to this Lease.'
                    );
                }

                if (
                    ! in_array(
                        $invoice->type,
                        [
                            'rent',
                            'security_deposit_debt',
                        ],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'The selected receivable cannot be settled using Security Deposit.'
                    );
                }

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

                $available =
                    (int) $account->balance();

                if ($available < $amount) {
                    throw new RuntimeException(
                        'Security Deposit application exceeds the available balance.'
                    );
                }

                /*
                 * Invoice::outstandingAmount() is the authoritative
                 * settlement calculation. It includes ordinary Payments,
                 * Rent Reserve, Consumable Advance, and prior manual
                 * Security Deposit applications.
                 */
                $outstanding =
                    $invoice->outstandingAmount();

                if ($outstanding <= 0) {
                    throw new RuntimeException(
                        'The selected receivable has no outstanding balance.'
                    );
                }

                if ($amount > $outstanding) {
                    throw new RuntimeException(
                        'Security Deposit application exceeds the receivable outstanding balance.'
                    );
                }

                $transaction =
                    TenantFundTransaction::create([
                        'tenant_fund_account_id' => $account->id,

                        'direction' => 'debit',

                        'category' => 'deposit_deduction',

                        'amount' => $amount,

                        'transaction_date' => $transactionDate,

                        'reference' => $invoice->invoice_number,

                        'notes' => $notes
                            ?? 'Security Deposit manually applied to Lease receivable.',
                    ]);

                $application =
                    SecurityDepositApplication::create([
                        'lease_id' => $lease->id,

                        'invoice_id' => $invoice->id,

                        'tenant_fund_transaction_id' => $transaction->id,

                        'amount' => $amount,

                        'application_date' => $transactionDate,

                        'notes' => $notes,
                    ]);

                $this->journal->post(
                    application: $application,
                    transaction: $transaction,
                    invoice: $invoice
                );

                return $application->refresh();
            }
        );
    }
}
