<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Allocates tenant Payments to outstanding Invoices using FIFO.
 *
 * Patrimoine's allocation rules are:
 *
 * 1. Find outstanding Invoices belonging to the Payment's Lease.
 * 2. Process the oldest due Invoice first.
 * 3. Fully settle that Invoice where possible.
 * 4. Continue to the next Invoice until the Payment is exhausted.
 * 5. Leave any excess Payment amount unapplied.
 *
 * Important accounting distinction:
 *
 * - Rent Invoices create Owner rent entitlement and Management Fees when
 *   tenant money is collected.
 * - Non-rent receivables, such as Security Deposit close-out debt, may still
 *   be settled through normal PaymentAllocation records but must not enter
 *   the Owner rent-accounting pipeline.
 *
 * Tenant money already classified into Rent Reserve, Consumable Advance or
 * Security Deposit is excluded from ordinary Payment allocation so the same
 * money can never be spent twice.
 *
 * The service runs inside a database transaction so a failed allocation
 * cannot leave Patrimoine in a partially updated accounting state.
 */
class PaymentAllocationService
{
    /**
     * Create the Payment allocation service.
     *
     * OwnerAccountingService is used only when a Payment allocation settles
     * contractual rent.
     */
    public function __construct(
        private readonly OwnerAccountingService $ownerAccountingService
    ) {}

    /**
     * Allocate all currently available funds from a Payment.
     */
    public function allocate(Payment $payment): Payment
    {
        return DB::transaction(
            function () use ($payment): Payment {
                /*
                 * Reload the Payment so all allocation calculations reflect
                 * the latest persisted accounting state.
                 */
                $payment->refresh();

                /*
                 * Only genuinely free Payment money may be allocated.
                 *
                 * Payment::allocatableAmount() removes:
                 *
                 * - amounts already allocated to Invoices; and
                 * - amounts already classified into tenant-held funds.
                 *
                 * This prevents Rent Reserve, Consumable Advance and Security
                 * Deposit money from being used again through ordinary FIFO
                 * Invoice settlement.
                 */
                $remaining =
                    $payment->allocatableAmount();

                if ($remaining <= 0) {
                    return $payment;
                }

                /*
                 * Only outstanding financial obligations participate in FIFO.
                 *
                 * Ordering first by due date and then by database ID gives a
                 * deterministic sequence when several Invoices share the same
                 * due date.
                 */
                $invoices = Invoice::query()
                    ->where(
                        'lease_id',
                        $payment->lease_id
                    )
                    ->whereIn(
                        'status',
                        [
                            'issued',
                            'partial',
                        ]
                    )
                    ->orderBy(
                        'due_date'
                    )
                    ->orderBy(
                        'id'
                    )
                    ->lockForUpdate()
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($remaining <= 0) {
                        break;
                    }

                    /*
                     * Invoice balance remains derived from its complete
                     * settlement history.
                     */
                    $outstanding =
                        $invoice->outstandingAmount();

                    if ($outstanding <= 0) {
                        continue;
                    }

                    /*
                     * Apply only the smaller of:
                     *
                     * - the Payment money still available; or
                     * - the Invoice amount still outstanding.
                     */
                    $allocationAmount =
                        min(
                            $remaining,
                            $outstanding
                        );

                    $allocation =
                        $payment
                            ->allocations()
                            ->create([
                                'invoice_id' => $invoice->id,

                                'amount' => $allocationAmount,
                            ]);

                    /*
                     * Only contractual rent enters the Owner rent-accounting
                     * pipeline.
                     *
                     * A Security Deposit debt Invoice represents recovery of a
                     * tenant close-out receivable. Although it is settled using
                     * an ordinary PaymentAllocation, that collection is not rent
                     * and must therefore never:
                     *
                     * - create Owner rent entitlement; or
                     * - generate a rent Management Fee.
                     *
                     * Invoice::isRentInvoice() is the authoritative distinction
                     * between rent and other tenant receivables.
                     */
                    if ($invoice->isRentInvoice()) {
                        $this
                            ->ownerAccountingService
                            ->postCollectedRentEntitlement(
                                $allocation
                            );

                        $this
                            ->ownerAccountingService
                            ->postManagementFee(
                                $allocation
                            );
                    }

                    /*
                     * Reduce the Payment amount still available for the next
                     * Invoice in FIFO order.
                     */
                    $remaining -=
                        $allocationAmount;

                    /*
                     * Invoice status reflects its settlement state after this
                     * allocation.
                     *
                     * PaymentAllocation records remain the accounting source
                     * of truth for how much money was actually applied.
                     */
                    $invoice->update([
                        'status' => $invoice->outstandingAmount() === 0
                                ? 'paid'
                                : 'partial',
                    ]);
                }

                return $payment->refresh();
            }
        );
    }

    /**
     * Validate that a Payment has not somehow been over-committed.
     *
     * This guard protects against corrupt manual data or future code paths
     * that bypass the normal allocation and tenant-fund services.
     *
     * A Payment's total committed amount consists of:
     *
     * - money allocated to Invoices; plus
     * - money classified into tenant-held funds.
     */
    public function assertNotOverAllocated(
        Payment $payment
    ): void {
        $committed =
            $payment->allocatedAmount()
            + $payment->classifiedFundAmount();

        if ($committed > $payment->amount) {
            throw new RuntimeException(
                'Payment allocations and tenant-fund classifications exceed the amount received.'
            );
        }
    }
}
