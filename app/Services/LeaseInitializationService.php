<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use Carbon\Carbon;

/**
 * Initializes records that should already exist when a Lease is entered.
 *
 * This is particularly important for backdated Leases. Patrimoine must
 * reconstruct both billing and, when explicitly confirmed by the operator,
 * historical money already received.
 *
 * Contractual Advance Payment configuration alone never implies receipt.
 * Historical financial reconstruction happens only when
 * `advance_received` is explicitly supplied as true.
 */
class LeaseInitializationService
{
    public function __construct(
        private readonly InvoiceGenerationService $invoiceGeneration,
        private readonly PaymentAllocationService $paymentAllocation,
        private readonly TenantFundAllocationService $tenantFundAllocation,
        private readonly OwnerAccountingService $ownerAccounting
    ) {}

    /**
     * Bring a newly-created Lease up to its correct operational position.
     *
     * @param  array<string, mixed>  $openingFinancialData
     */
    public function initialize(
        Lease $lease,
        array $openingFinancialData = [],
        ?Carbon $throughDate = null
    ): Lease {
        /*
         * -------------------------------------------------------------
         * Fund account provisioning
         * -------------------------------------------------------------
         *
         * V1.0.8: every Lease carries all three tenant fund accounts from
         * the moment it exists, rather than materializing them on first
         * funding. Tenants > Accounts therefore always shows the full
         * held-funds position (zero balances included), and Transfers can
         * target any account without it having been funded before.
         */
        $this->provisionFundAccounts($lease);

        /*
         * -------------------------------------------------------------
         * Billing reconstruction
         * -------------------------------------------------------------
         */

        if (
            in_array(
                $lease->status,
                ['active', 'notice'],
                true
            )
        ) {
            $throughDate ??=
                now()->startOfDay();

            if (
                $lease->start_date
                    ->lte($throughDate)
            ) {
                $this->invoiceGeneration
                    ->generateDueInvoices(
                        $lease,
                        $throughDate
                    );
            }
        }

        /*
         * -------------------------------------------------------------
         * The Security Deposit
         * -------------------------------------------------------------
         *
         * V1.0.43. Every Lease has owned a Security Deposit account since
         * V1.0.8, and the Lease form has always asked what the deposit is
         * — but nothing ever funded it. The contract said 1,000 and the
         * account said nothing, on every Lease ever entered, and the first
         * anybody knew of it was reaching for money that was not there.
         *
         * A deposit is taken when the tenancy is agreed, so entering it on
         * the Lease receives it. It is funded BEFORE the advance so that
         * FIFO rent settlement can never reach money the tenant is owed
         * back at the end of the letting.
         */
        $this->initializeSecurityDeposit(
            $lease,
            $openingFinancialData
        );

        /*
         * -------------------------------------------------------------
         * Historical money reconstruction
         * -------------------------------------------------------------
         *
         * Merely configuring contractual Advance Payment does not mean
         * Patrimoine received it.
         */
        if (
            ! (bool) (
                $openingFinancialData[
                    'advance_received'
                ] ?? false
            )
        ) {
            return $lease->refresh();
        }

        $this->initializeReceivedAdvance(
            $lease,
            $openingFinancialData
        );

        return $lease->refresh();
    }

    /**
     * Receive the Security Deposit agreed on the Lease.
     *
     * One Payment, classified in full into the Lease's own Security
     * Deposit account. It is deliberately NOT passed through ordinary FIFO
     * allocation: a deposit is the tenant's money held against the end of
     * the letting, and settling rent with it would be spending money that
     * has to be given back.
     *
     * Idempotent through is_opening_deposit, because initialization runs
     * again on every Lease update and a deposit taken twice is a deposit
     * owed twice.
     *
     * @param  array<string, mixed>  $data
     */
    private function initializeSecurityDeposit(
        Lease $lease,
        array $data
    ): void {
        $amount = (int) $lease->security_deposit_amount;

        if ($amount <= 0) {
            return;
        }

        $existing = Payment::query()
            ->where('lease_id', $lease->id)
            ->where('is_opening_deposit', true)
            ->exists();

        if ($existing) {
            return;
        }

        /*
         * The date the deposit actually changed hands. Deposits are
         * routinely taken before a tenancy begins — they are usually what
         * secures the unit — so this date is deliberately unbounded by the
         * Lease start. Absent, the Lease start stands in for it.
         */
        $receivedOn =
            $data['security_deposit_received_date']
            ?? $lease->start_date->toDateString();

        $method =
            $data['security_deposit_received_method']
            ?? 'bank_transfer';

        $payment = Payment::create([
            'lease_id' => $lease->id,

            'amount' => $amount,

            'payment_date' => $receivedOn,

            'payment_method' => $method,

            'reference' =>
                $data['security_deposit_received_reference'] ?? null,

            /*
             * As with the opening advance, Patrimoine cannot safely infer
             * which User, if any, took cash it is only now being told
             * about. The supplied name is kept as a frozen snapshot and no
             * User relationship is claimed.
             */
            'cash_receiver_user_id' => null,

            'cash_receiver_name' => (
                $method === 'cash'
                    ? ($data['security_deposit_received_collector'] ?? null)
                    : null
            ),

            'notes' => 'Security Deposit received at Lease opening.',

            'is_opening_deposit' => true,
        ]);

        $this->tenantFundAllocation
            ->allocate(
                payment: $payment,
                fundType: 'security_deposit',
                amount: $amount,
                transactionDate: $receivedOn,
                reference: $payment->reference,
                notes: 'Security Deposit received at Lease opening.'
            );
    }

    /**
     * Reconstruct an Advance Payment that was already received historically.
     *
     * Accounting order is deliberate:
     *
     * 1. Create the actual Payment.
     * 2. Protect Rent Reserve.
     * 3. Allocate remaining available cash FIFO to historical rent.
     * 4. Preserve any surplus as Consumable Advance.
     * 5. Post the one-time Agent commission.
     *
     * PaymentAllocationService automatically posts:
     * - owner rent entitlement; and
     * - Managing Organisation fee.
     *
     * @param  array<string, mixed>  $data
     */
    private function initializeReceivedAdvance(

        Lease $lease,

        array $data

    ): Payment {

        /*

         * A Lease may have only one historical opening Advance Payment.

         *

         * This makes initialization idempotent. Editing the Lease again or

         * accidentally resubmitting the same opening instructions must never

         * duplicate tenant cash, Invoice settlement or owner accounting.

         */

        $existingOpeningPayment = Payment::query()

            ->where('lease_id', $lease->id)

            ->where('is_opening_advance', true)

            ->first();

        if ($existingOpeningPayment !== null) {

            return $existingOpeningPayment;

        }

        $paymentDate =

            (string) $data[

                'advance_received_date'

            ];

        /*

         * One Payment represents the actual historical tenant money received.

         */
        $payment = Payment::create([
            'lease_id' => $lease->id,

            'amount' => $lease->advance_payment_amount,

            'payment_date' => $paymentDate,

            'payment_method' => $data[
                    'advance_received_method'
                ],

            'reference' => $data[
                    'advance_received_reference'
                ] ?? null,

            /*
             * V1.0.5 historical opening-payment attribution.
             *
             * This payment reconstructs money that was received before the
             * Lease was entered into Patrimoine. It must therefore NOT be
             * attributed to the currently authenticated User.
             *
             * For historical cash, preserve the supplied receiver name as a
             * frozen snapshot only. There is deliberately no User relationship
             * because Patrimoine cannot safely infer which User, if any,
             * received the historical cash.
             *
             * Electronic historical payments have no Cash Receiver.
             *
             * collector_name is legacy-only and is not populated by new
             * V1.0.5 records.
             */
            'cash_receiver_user_id' => null,

            'cash_receiver_name' => (
                ($data['advance_received_method'] ?? null) === 'cash'
                    ? ($data['advance_received_collector'] ?? null)
                    : null
            ),

            'notes' => 'Historical Advance Payment recorded during Lease opening.',
            'is_opening_advance' => true,
        ]);

        /*
         * -------------------------------------------------------------
         * Protect Rent Reserve FIRST
         * -------------------------------------------------------------
         *
         * This prevents ordinary FIFO rent settlement from consuming money
         * contractually protected until the termination workflow.
         */
        if ($lease->rent_reserve_amount > 0) {
            $this->tenantFundAllocation
                ->allocate(
                    payment: $payment,
                    fundType: 'rent_reserve',
                    amount: $lease->rent_reserve_amount,
                    transactionDate: $paymentDate,
                    reference: $payment->reference,
                    notes: 'Historical Rent Reserve funded from Lease opening Advance Payment.'
                );
        }

        /*
         * -------------------------------------------------------------
         * Settle historical rent FIFO
         * -------------------------------------------------------------
         *
         * PaymentAllocationService sees only money that remains genuinely
         * allocatable after Rent Reserve classification.
         *
         * It also posts owner entitlement and Management Organisation fees
         * for every allocation created.
         */
        $this->paymentAllocation
            ->allocate($payment);

        $payment->refresh();

        /*
         * -------------------------------------------------------------
         * Preserve remaining surplus as Consumable Advance
         * -------------------------------------------------------------
         *
         * If all currently outstanding rent has been paid and received money
         * still remains, that cash belongs to the tenant's advance balance.
         */
        $remaining =
            $payment->allocatableAmount();

        if ($remaining > 0) {
            $this->tenantFundAllocation
                ->allocate(
                    payment: $payment,
                    fundType: 'consumable_advance',
                    amount: $remaining,
                    transactionDate: $paymentDate,
                    reference: $payment->reference,
                    notes: 'Remaining historical Advance Payment retained as Consumable Advance.'
                );
        }

        /*
         * -------------------------------------------------------------
         * One-time Agent commission
         * -------------------------------------------------------------
         *
         * Once the historical Advance is confirmed as actually received,
         * this Lease has entered the financial accounting history.
         *
         * postAgentCommission() is idempotent and therefore safe against
         * accidental repeat processing.
         */
        $this->ownerAccounting
            ->postAgentCommission(
                $lease
            );

        return $payment->refresh();
    }

    /**
     * Ensure the Lease owns one account of every tenant fund type.
     *
     * Idempotent: the (lease_id, type) unique constraint means repeat
     * initialization simply finds the existing accounts.
     */
    private function provisionFundAccounts(
        Lease $lease
    ): void {
        foreach ([
            'rent_reserve',
            'consumable_advance',
            'security_deposit',
        ] as $type) {
            TenantFundAccount::firstOrCreate(
                [
                    'lease_id' => $lease->id,
                    'type' => $type,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}
