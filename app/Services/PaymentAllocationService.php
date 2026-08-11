<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Allocates tenant payments to outstanding invoices using FIFO.
 *
 * Patrimoine's allocation rule is:
 * 1. Find outstanding invoices for the Payment's Lease.
 * 2. Process the oldest due invoice first.
 * 3. Fully settle it where possible.
 * 4. Continue to the next invoice until the Payment is exhausted.
 * 5. Leave any remaining amount unapplied.
 *
 * The service runs inside a database transaction so a failed allocation
 * cannot leave a partially updated accounting state.
 */
class PaymentAllocationService
{
    /**
     * Allocate all currently unapplied funds from a Payment.
     */
    public function allocate(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            /*
             * Reload the Payment so calculations reflect allocations that
             * may already exist before this service is called.
             */
            $payment->refresh();

            $remaining = $payment->unallocatedAmount();

            if ($remaining <= 0) {
                return $payment;
            }

            /*
             * Only active financial invoices participate in settlement.
             *
             * FIFO is determined first by due date and then by database ID
             * so invoices sharing the same due date have deterministic order.
             */
            $invoices = Invoice::query()
                ->where('lease_id', $payment->lease_id)
                ->whereIn('status', ['issued', 'partial'])
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $outstanding = $invoice->outstandingAmount();

                if ($outstanding <= 0) {
                    continue;
                }

                $allocationAmount = min($remaining, $outstanding);

                $allocation = $payment->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                ]);

                /*
                * Patrimoine uses cash-basis owner accounting.
                *
                * Owner entitlement is therefore created at exactly the point where
                * tenant money is allocated to an Invoice.
                */
                $this->ownerAccountingService
                    ->postCollectedRentEntitlement($allocation);

                $remaining -= $allocationAmount;

                /*
                 * Invoice status reflects the settlement state after this
                 * allocation. Payment allocations remain the accounting
                 * source of truth for the actual paid amount.
                 */
                $invoice->update([
                    'status' => $invoice->outstandingAmount() === 0
                        ? 'paid'
                        : 'partial',
                ]);
            }

            return $payment->refresh();
        });
    }

    /**
     * Validate that a Payment has not somehow been over-allocated.
     *
     * This guard protects against bad manual data or future code paths
     * bypassing the normal allocation service.
     */
    public function assertNotOverAllocated(Payment $payment): void
    {
        if ($payment->allocatedAmount() > $payment->amount) {
            throw new RuntimeException(
                'Payment allocations exceed the amount received.'
            );
        }
    }
    /**
     * Create the payment-allocation service.
     *
     * Owner accounting is triggered immediately after each successful tenant
     * payment allocation so collected rent and owner funds remain synchronized.
     */
    public function __construct(
        private readonly OwnerAccountingService $ownerAccountingService
    ) {
    }
}
