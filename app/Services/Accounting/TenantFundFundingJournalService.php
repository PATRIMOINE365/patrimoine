<?php

namespace App\Services\Accounting;

use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Mirrors post-cutover Tenant fund funding into the immutable
 * Financial Journal.
 *
 * Funding is recognized only when previously unallocated tenant money is
 * explicitly classified into:
 *
 * - Rent Reserve;
 * - Consumable Advance; or
 * - Security Deposit.
 *
 * Before the controlled V1.0.5 accounting cutover this service is a no-op.
 *
 * Consumption, deductions, refunds and withdrawals are deliberately not
 * handled here; they are separate accounting events.
 */
class TenantFundFundingJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting
    ) {
    }

    public function post(
        TenantFundTransaction $transaction
    ): void {
        if (! $this->runtimeGate->enabled()) {
            return;
        }

        $transaction->loadMissing([
            'account',
            'payment',
        ]);

        $account =
            $transaction->account;

        $payment =
            $transaction->payment;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Tenant fund funding transaction has no valid account.'
            );
        }

        if (! $payment instanceof Payment) {
            /*
             * Funding Journal entries require a real incoming Payment.
             *
             * Tenant fund transactions without Payment provenance belong to
             * other workflows such as consumption, deductions or settlement.
             */
            return;
        }

        if (
            (string) $transaction->direction
            !== 'credit'
        ) {
            return;
        }

        [
            $event,
            $expectedFundType,
        ] =
            $this->fundingDefinition(
                (string) $transaction->category
            );

        if (
            (string) $account->type
            !== $expectedFundType
        ) {
            throw new RuntimeException(
                sprintf(
                    'Tenant fund funding category %s does not match account type %s.',
                    (string) $transaction->category,
                    (string) $account->type
                )
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Tenant fund funding Journal amount must be greater than zero.'
            );
        }

        $paymentMethod =
            trim(
                (string) $payment->payment_method
            );

        if ($paymentMethod === '') {
            throw new RuntimeException(
                'Tenant fund source Payment method is required.'
            );
        }

        $mapping =
            $this->eventMap->contextual(
                $event
            );

        $assetAccount =
            $this->eventMap->paymentAsset(
                $paymentMethod
            );

        $liabilityAccount =
            $mapping['credit']
            ?? null;

        if (
            ! is_string($liabilityAccount)
            || trim($liabilityAccount) === ''
        ) {
            throw new RuntimeException(
                'Tenant fund funding accounting mapping has no liability account.'
            );
        }

        /*
         * Protect the permanent AccountingEventMap against accidental drift.
         */
        $expectedLiability =
            $this->eventMap->tenantFundLiability(
                $expectedFundType
            );

        if (
            $liabilityAccount
            !== $expectedLiability
        ) {
            throw new RuntimeException(
                'Tenant fund funding accounting mapping is inconsistent.'
            );
        }

        $this->journalPosting->post(
            [
                'journal_date' =>
                    $this->transactionDate(
                        $transaction,
                        $payment
                    ),

                'transaction_type' =>
                    $event,

                'description' =>
                    $this->description(
                        $expectedFundType,
                        $payment
                    ),

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'idempotency_key' =>
                    self::idempotencyKey(
                        $transaction
                    ),

                'snapshot' => [
                    'tenant_fund_transaction_id' =>
                        $transaction->id,

                    'tenant_fund_account_id' =>
                        $account->id,

                    'lease_id' =>
                        $account->lease_id,

                    'payment_id' =>
                        $payment->id,

                    'fund_type' =>
                        $expectedFundType,

                    'category' =>
                        $transaction->category,

                    'payment_method' =>
                        $paymentMethod,

                    'amount' =>
                        $amount,

                    'reference' =>
                        $transaction->reference,
                ],
            ],
            [
                [
                    'account_code' =>
                        $assetAccount,

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        'Tenant funds received',
                ],
                [
                    'account_code' =>
                        $liabilityAccount,

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Tenant funds held',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'tenant-fund-funding:'
            .$transaction->id;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fundingDefinition(
        string $category
    ): array {
        return match ($category) {
            'reserve_funding' => [
                AccountingEventMap::
                    EVENT_RENT_RESERVE_FUNDING,

                'rent_reserve',
            ],

            'advance_funding' => [
                AccountingEventMap::
                    EVENT_ADVANCE_FUNDING,

                'consumable_advance',
            ],

            'deposit_funding' => [
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_FUNDING,

                'security_deposit',
            ],

            /*
             * This service should be called only at funding boundaries.
             * An unknown credit category is therefore unsafe rather than
             * silently treated as one of the three supported liabilities.
             */
            default =>
                throw new RuntimeException(
                    'Unsupported Tenant fund funding category: '
                    .$category
                ),
        };
    }

    private function transactionDate(
        TenantFundTransaction $transaction,
        Payment $payment
    ): string {
        if ($transaction->transaction_date !== null) {
            return Carbon::parse(
                $transaction->transaction_date
            )->toDateString();
        }

        if ($payment->payment_date !== null) {
            return Carbon::parse(
                $payment->payment_date
            )->toDateString();
        }

        return now()
            ->toDateString();
    }

    private function description(
        string $fundType,
        Payment $payment
    ): string {
        $label =
            match ($fundType) {
                'rent_reserve' =>
                    'Rent Reserve funding',

                'consumable_advance' =>
                    'Consumable Advance funding',

                'security_deposit' =>
                    'Security Deposit funding',

                default =>
                    'Tenant fund funding',
            };

        $reference =
            trim(
                (string) (
                    $payment->reference
                    ?? ''
                )
            );

        return
            $reference !== ''
                ? $label.' '.$reference
                : $label.' #'.$payment->id;
    }
}
