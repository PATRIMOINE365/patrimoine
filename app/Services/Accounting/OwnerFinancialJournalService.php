<?php

namespace App\Services\Accounting;

use App\Models\Lease;
use App\Models\OwnerExpense;
use App\Models\OwnerPayout;
use App\Models\OwnerTransaction;
use App\Models\PaymentAllocation;
use RuntimeException;

/**
 * Post ordinary post-cutover Owner financial events into Patrimoine's
 * immutable Financial Journal.
 *
 * Historical pre-cutover Owner activity is represented only through the
 * controlled V1.0.5 opening balance.
 *
 * Covered events:
 *
 * - Owner Deposit;
 * - Owner Payout;
 * - Property Expense;
 * - collected-rent Owner entitlement;
 * - Management Fee;
 * - Agent Commission.
 *
 * Owner Adjustments have their own standardized Journal service.
 */
final class OwnerFinancialJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtime,
        private readonly AccountingEventMap $events,
        private readonly JournalPostingService $journal,
    ) {}

    public function postDeposit(
        OwnerTransaction $transaction
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        $this->assertTransaction(
            transaction: $transaction,
            category: 'owner_deposit',
            direction: 'credit',
        );

        $method = trim(
            (string) $transaction->payment_method
        );

        if ($method === '') {
            throw new RuntimeException(
                'Owner Deposit payment method is required for Financial Journal posting.'
            );
        }

        $mapping = $this->events->contextual(
            AccountingEventMap::EVENT_OWNER_DEPOSIT
        );

        $asset = $this->events->paymentAsset(
            $method
        );

        $payable = $mapping['credit']
            ?? null;

        if (
            ! is_string($payable)
            || trim($payable) === ''
        ) {
            throw new RuntimeException(
                'Owner Deposit accounting mapping is invalid.'
            );
        }

        $amount = (int) $transaction->amount;

        $this->journal->post(
            [
                'journal_date' =>
                    $transaction
                        ->transaction_date
                        ->toDateString(),

                'transaction_type' =>
                    AccountingEventMap::EVENT_OWNER_DEPOSIT,

                'description' =>
                    'Owner Deposit #'.$transaction->id,

                'source_type' =>
                    OwnerTransaction::class,

                'source_id' =>
                    $transaction->id,

                'idempotency_key' =>
                    'owner-deposit:'.$transaction->id,

                'snapshot' =>
                    $this->transactionSnapshot(
                        $transaction
                    ),
            ],
            [
                [
                    'account_code' => $asset,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Owner funds received',
                ],
                [
                    'account_code' => $payable,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Increase Owner Funds Payable',
                ],
            ]
        );
    }

    public function postPayout(
        OwnerPayout $payout,
        OwnerTransaction $transaction
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        $this->assertTransaction(
            transaction: $transaction,
            category: 'payout',
            direction: 'debit',
        );

        if (
            (int) $transaction->owner_account_id
                !== (int) $payout->owner_account_id
            || (int) $transaction->amount
                !== (int) $payout->amount
        ) {
            throw new RuntimeException(
                'Owner Payout Journal context is inconsistent.'
            );
        }

        $method = trim(
            (string) $payout->payment_method
        );

        if ($method === '') {
            throw new RuntimeException(
                'Owner Payout payment method is required for Financial Journal posting.'
            );
        }

        $mapping = $this->events->contextual(
            AccountingEventMap::EVENT_OWNER_PAYOUT
        );

        $payable = $mapping['debit']
            ?? null;

        if (
            ! is_string($payable)
            || trim($payable) === ''
        ) {
            throw new RuntimeException(
                'Owner Payout accounting mapping is invalid.'
            );
        }

        $asset = $this->events->paymentAsset(
            $method
        );

        $amount = (int) $payout->amount;

        $this->journal->post(
            [
                'journal_date' =>
                    $payout
                        ->payout_date
                        ->toDateString(),

                'transaction_type' =>
                    AccountingEventMap::EVENT_OWNER_PAYOUT,

                'description' =>
                    'Owner Payout #'.$payout->id,

                'source_type' =>
                    OwnerPayout::class,

                'source_id' =>
                    $payout->id,

                'idempotency_key' =>
                    'owner-payout:'.$payout->id,

                'snapshot' => [
                    'owner_payout_id' =>
                        $payout->id,

                    'owner_transaction_id' =>
                        $transaction->id,

                    'owner_account_id' =>
                        $payout->owner_account_id,

                    'payment_method' =>
                        $method,

                    'amount' =>
                        $amount,

                    'reference' =>
                        $payout->reference,
                ],
            ],
            [
                [
                    'account_code' => $payable,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Reduce Owner Funds Payable',
                ],
                [
                    'account_code' => $asset,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Owner payout paid',
                ],
            ]
        );
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    public function postExpense(
        OwnerExpense $expense,
        array $transactions
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        $this->assertBatch(
            transactions: $transactions,
            category: 'expense',
            direction: 'debit',
        );

        $amount = $this->batchAmount(
            $transactions
        );

        if (
            $amount !== (int) $expense->amount
        ) {
            throw new RuntimeException(
                'Owner Expense Journal allocation does not equal the source expense.'
            );
        }

        $mapping = $this->events->fixed(
            AccountingEventMap::EVENT_OWNER_EXPENSE
        );

        $this->postFixed(
            event:
                AccountingEventMap::EVENT_OWNER_EXPENSE,

            journalDate:
                $expense
                    ->expense_date
                    ->toDateString(),

            description:
                'Property expense #'.$expense->id,

            sourceType:
                OwnerExpense::class,

            sourceId:
                $expense->id,

            idempotencyKey:
                'owner-expense:'.$expense->id,

            amount:
                $amount,

            mapping:
                $mapping,

            snapshot: [
                'owner_expense_id' =>
                    $expense->id,

                'building_id' =>
                    $expense->building_id,

                'unit_id' =>
                    $expense->unit_id,

                'amount' =>
                    (int) $expense->amount,

                'reference' =>
                    $expense->reference,

                'owner_transaction_ids' =>
                    $this->transactionIds(
                        $transactions
                    ),
            ],
        );
    }

    /**
     * V1.0.8: post one expense bill payment.
     *
     * Bills no longer settle themselves at creation; each explicit
     * payment (possibly partial) posts its own entry using the same
     * fixed owner-expense mapping, keyed to the payment transaction.
     */
    public function postExpenseBillPayment(
        OwnerTransaction $payment
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        if (
            $payment->direction !== 'debit'
            || $payment->category !== 'expense'
            || $payment->owner_expense_bill_id === null
        ) {
            throw new RuntimeException(
                'Invalid owner expense bill payment transaction.'
            );
        }

        $amount = (int) $payment->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Owner expense bill payment Journal amount must be greater than zero.'
            );
        }

        $mapping = $this->events->fixed(
            AccountingEventMap::EVENT_OWNER_EXPENSE
        );

        $this->postFixed(
            event:
                AccountingEventMap::EVENT_OWNER_EXPENSE,

            journalDate:
                $payment
                    ->transaction_date
                    ->toDateString(),

            description:
                'Owner expense bill payment #'.$payment->id,

            sourceType:
                OwnerTransaction::class,

            sourceId:
                $payment->id,

            idempotencyKey:
                'owner-expense-bill-payment:'.$payment->id,

            amount:
                $amount,

            mapping:
                $mapping,

            snapshot: [
                'owner_transaction_id' =>
                    $payment->id,

                'owner_expense_bill_id' =>
                    $payment->owner_expense_bill_id,

                'owner_account_id' =>
                    $payment->owner_account_id,

                'funding_source' =>
                    $payment->funding_source,

                'amount' =>
                    $amount,

                'reference' =>
                    $payment->reference,
            ],
        );
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    public function postRentEntitlement(
        PaymentAllocation $allocation,
        array $transactions
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        $this->assertBatch(
            transactions: $transactions,
            category: 'rent_entitlement',
            direction: 'credit',
        );

        $amount = $this->batchAmount(
            $transactions
        );

        if (
            $amount !== (int) $allocation->amount
        ) {
            throw new RuntimeException(
                'Owner rent entitlement Journal amount does not equal the collected rent allocation.'
            );
        }

        $mapping = $this->events->fixed(
            AccountingEventMap::EVENT_OWNER_RENT_ENTITLEMENT
        );

        $this->postFixed(
            event:
                AccountingEventMap::EVENT_OWNER_RENT_ENTITLEMENT,

            journalDate:
                $transactions[0]
                    ->transaction_date
                    ->toDateString(),

            description:
                'Owner rent entitlement for Payment Allocation #'
                .$allocation->id,

            sourceType:
                PaymentAllocation::class,

            sourceId:
                $allocation->id,

            idempotencyKey:
                'owner-rent-entitlement:'.$allocation->id,

            amount:
                $amount,

            mapping:
                $mapping,

            snapshot: [
                'payment_allocation_id' =>
                    $allocation->id,

                'payment_id' =>
                    $allocation->payment_id,

                'invoice_id' =>
                    $allocation->invoice_id,

                'amount' =>
                    $amount,

                'owner_transaction_ids' =>
                    $this->transactionIds(
                        $transactions
                    ),
            ],
        );
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    public function postManagementFee(
        PaymentAllocation $allocation,
        array $transactions
    ): void {
        if (
            ! $this->runtime->enabled()
            || $transactions === []
        ) {
            return;
        }

        $this->assertBatch(
            transactions: $transactions,
            category: 'management_fee',
            direction: 'debit',
        );

        $amount = $this->batchAmount(
            $transactions
        );

        $mapping = $this->events->fixed(
            AccountingEventMap::EVENT_MANAGEMENT_FEE
        );

        /*
         * Percentage fees are naturally one event per allocation.
         * Fixed fees are also created from the first collection only, so the
         * persisted Owner transaction's Payment Allocation remains a stable
         * source identity.
         */
        $sourceAllocationId =
            $transactions[0]->payment_allocation_id
            ?? $allocation->id;

        $this->postFixed(
            event:
                AccountingEventMap::EVENT_MANAGEMENT_FEE,

            journalDate:
                $transactions[0]
                    ->transaction_date
                    ->toDateString(),

            description:
                'Management Fee for Payment Allocation #'
                .$sourceAllocationId,

            sourceType:
                PaymentAllocation::class,

            sourceId:
                (int) $sourceAllocationId,

            idempotencyKey:
                'management-fee:'
                .$sourceAllocationId,

            amount:
                $amount,

            mapping:
                $mapping,

            snapshot: [
                'payment_allocation_id' =>
                    (int) $sourceAllocationId,

                'invoice_id' =>
                    $transactions[0]->invoice_id,

                'lease_id' =>
                    $transactions[0]->lease_id,

                'amount' =>
                    $amount,

                'owner_transaction_ids' =>
                    $this->transactionIds(
                        $transactions
                    ),
            ],
        );
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    public function postAgentCommission(
        Lease $lease,
        array $transactions
    ): void {
        if (
            ! $this->runtime->enabled()
            || $transactions === []
        ) {
            return;
        }

        $this->assertBatch(
            transactions: $transactions,
            category: 'agent_commission',
            direction: 'debit',
        );

        $amount = $this->batchAmount(
            $transactions
        );

        if (
            $amount
            !== (int) $lease->agent_commission_amount
        ) {
            throw new RuntimeException(
                'Agent Commission Journal allocation does not equal the Lease commission.'
            );
        }

        $mapping = $this->events->fixed(
            AccountingEventMap::EVENT_AGENT_COMMISSION
        );

        $this->postFixed(
            event:
                AccountingEventMap::EVENT_AGENT_COMMISSION,

            journalDate:
                $lease->start_date
                    ->toDateString(),

            description:
                'Agent Commission for Lease #'.$lease->id,

            sourceType:
                Lease::class,

            sourceId:
                $lease->id,

            idempotencyKey:
                'agent-commission:'.$lease->id,

            amount:
                $amount,

            mapping:
                $mapping,

            snapshot: [
                'lease_id' =>
                    $lease->id,

                'agent_id' =>
                    $lease->agent_id,

                'amount' =>
                    $amount,

                'owner_transaction_ids' =>
                    $this->transactionIds(
                        $transactions
                    ),
            ],
        );
    }

    /**
     * @param array<string, string> $mapping
     * @param array<string, mixed> $snapshot
     */
    private function postFixed(
        string $event,
        string $journalDate,
        string $description,
        string $sourceType,
        int $sourceId,
        string $idempotencyKey,
        int $amount,
        array $mapping,
        array $snapshot
    ): void {
        $debit = $mapping['debit']
            ?? null;

        $credit = $mapping['credit']
            ?? null;

        if (
            ! is_string($debit)
            || trim($debit) === ''
            || ! is_string($credit)
            || trim($credit) === ''
        ) {
            throw new RuntimeException(
                'Owner financial accounting mapping is invalid.'
            );
        }

        if ($amount <= 0) {
            throw new RuntimeException(
                'Owner Financial Journal amount must be greater than zero.'
            );
        }

        $this->journal->post(
            [
                'journal_date' =>
                    $journalDate,

                'transaction_type' =>
                    $event,

                'description' =>
                    $description,

                'source_type' =>
                    $sourceType,

                'source_id' =>
                    $sourceId,

                'idempotency_key' =>
                    $idempotencyKey,

                'snapshot' =>
                    $snapshot,
            ],
            [
                [
                    'account_code' =>
                        $debit,

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        $description,
                ],
                [
                    'account_code' =>
                        $credit,

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        $description,
                ],
            ]
        );
    }

    private function assertTransaction(
        OwnerTransaction $transaction,
        string $category,
        string $direction
    ): void {
        if (! $transaction->exists) {
            throw new RuntimeException(
                'Owner transaction must be persisted before Financial Journal posting.'
            );
        }

        if (
            $transaction->category !== $category
            || $transaction->direction !== $direction
        ) {
            throw new RuntimeException(
                'Owner transaction does not match the expected accounting event.'
            );
        }

        if ((int) $transaction->amount <= 0) {
            throw new RuntimeException(
                'Owner Financial Journal amount must be greater than zero.'
            );
        }
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    private function assertBatch(
        array $transactions,
        string $category,
        string $direction
    ): void {
        if ($transactions === []) {
            throw new RuntimeException(
                'Owner financial event has no Owner ledger transactions.'
            );
        }

        foreach ($transactions as $transaction) {
            if (! $transaction instanceof OwnerTransaction) {
                throw new RuntimeException(
                    'Owner Financial Journal batch contains an invalid transaction.'
                );
            }

            $this->assertTransaction(
                transaction: $transaction,
                category: $category,
                direction: $direction,
            );
        }
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     */
    private function batchAmount(
        array $transactions
    ): int {
        return array_sum(
            array_map(
                fn (OwnerTransaction $transaction): int =>
                    (int) $transaction->amount,
                $transactions
            )
        );
    }

    /**
     * @param array<int, OwnerTransaction> $transactions
     * @return array<int>
     */
    private function transactionIds(
        array $transactions
    ): array {
        return array_values(
            array_map(
                fn (OwnerTransaction $transaction): int =>
                    (int) $transaction->id,
                $transactions
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionSnapshot(
        OwnerTransaction $transaction
    ): array {
        return [
            'owner_transaction_id' =>
                $transaction->id,

            'owner_account_id' =>
                $transaction->owner_account_id,

            'building_id' =>
                $transaction->building_id,

            'unit_id' =>
                $transaction->unit_id,

            'lease_id' =>
                $transaction->lease_id,

            'invoice_id' =>
                $transaction->invoice_id,

            'payment_allocation_id' =>
                $transaction->payment_allocation_id,

            'category' =>
                $transaction->category,

            'direction' =>
                $transaction->direction,

            'payment_method' =>
                $transaction->payment_method,

            'amount' =>
                (int) $transaction->amount,

            'reference' =>
                $transaction->reference,

            'cash_receiver_user_id' =>
                $transaction->cash_receiver_user_id,

            'cash_receiver_name' =>
                $transaction->cash_receiver_name,
        ];
    }
}
