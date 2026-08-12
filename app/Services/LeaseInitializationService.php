<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Payment;
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
    ) {
    }

    /**
     * Bring a newly-created Lease up to its correct operational position.
     *
     * @param array<string, mixed> $openingFinancialData
     */
    public function initialize(
        Lease $lease,
        array $openingFinancialData = [],
        ?Carbon $throughDate = null
    ): Lease {
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
     * @param array<string, mixed> $data
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
            'lease_id' =>
                $lease->id,

            'amount' =>
                $lease->advance_payment_amount,

            'payment_date' =>
                $paymentDate,

            'payment_method' =>
                $data[
                    'advance_received_method'
                ],

            'reference' =>
                $data[
                    'advance_received_reference'
                ] ?? null,

            'collector_name' =>
                $data[
                    'advance_received_collector'
                ] ?? null,

            'notes' =>
                'Historical Advance Payment recorded during Lease opening.',
            'is_opening_advance' =>

                true,
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
                    amount:
                        $lease->rent_reserve_amount,
                    transactionDate:
                        $paymentDate,
                    reference:
                        $payment->reference,
                    notes:
                        'Historical Rent Reserve funded from Lease opening Advance Payment.'
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
                    fundType:
                        'consumable_advance',
                    amount:
                        $remaining,
                    transactionDate:
                        $paymentDate,
                    reference:
                        $payment->reference,
                    notes:
                        'Remaining historical Advance Payment retained as Consumable Advance.'
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
}
