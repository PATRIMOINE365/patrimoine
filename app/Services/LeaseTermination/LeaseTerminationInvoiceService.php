<?php

namespace App\Services\LeaseTermination;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Services\Accounting\JournalReversalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Applies the financial Invoice boundary created by Lease termination.
 *
 * This service deliberately does not attempt to unwind financial history.
 *
 * Untouched future rent Invoices may be cancelled. If such an Invoice has
 * already been mirrored into the Financial Journal, its original rent
 * Invoice posting is reversed through the immutable Journal reversal
 * mechanism.
 *
 * An Invoice with settlement or downstream financial activity is preserved
 * for an explicit correction workflow.
 */
class LeaseTerminationInvoiceService
{
    public function __construct(
        private readonly JournalReversalService $journalReversal
    ) {}

    public function reconcile(
        Lease $lease,
        ?int $actorUserId = null
    ): void {
        DB::transaction(function () use (
            $lease,
            $actorUserId
        ): void {
            $lease = Lease::query()
                ->lockForUpdate()
                ->findOrFail($lease->id);

            if (
                $lease->status !== 'notice'
                || $lease->termination_date === null
            ) {
                throw new RuntimeException(
                    'Lease must be under termination notice with a Termination Date.'
                );
            }

            $terminationDate =
                Carbon::parse($lease->termination_date)
                    ->startOfDay();

            $invoices = Invoice::query()
                ->where('lease_id', $lease->id)
                ->where('type', 'rent')
                ->where('status', '!=', 'cancelled')
                ->orderBy('period_start')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                $periodStart =
                    Carbon::parse($invoice->period_start)
                        ->startOfDay();

                $periodEnd =
                    Carbon::parse($invoice->period_end)
                        ->startOfDay();

                /*
                 * Entirely after termination:
                 *
                 * These Invoices no longer represent rent that should become
                 * due under the terminating Lease.
                 */
                if ($periodStart->gt($terminationDate)) {
                    $this->cancelIfUntouched(
                        $invoice,
                        $lease,
                        $actorUserId
                    );

                    continue;
                }

                /*
                 * The Invoice that intersects the Termination Date is the
                 * final contractual period.
                 *
                 * Full:
                 *   preserve the full Invoice.
                 *
                 * None:
                 *   cancel it only when untouched.
                 *
                 * Prorate:
                 *   existing Invoice rewriting is intentionally not performed
                 *   here. Generation must create the correct final amount.
                 *   A financially touched pre-existing Invoice is preserved.
                 */
                if (
                    $periodStart->lte($terminationDate)
                    && $periodEnd->gte($terminationDate)
                ) {
                    if (
                        $lease->termination_final_rent_mode
                        === 'none'
                    ) {
                        $this->cancelIfUntouched(
                            $invoice,
                            $lease,
                            $actorUserId
                        );
                    }

                    continue;
                }
            }
        });
    }

    public function hasFinancialActivity(
        Invoice $invoice
    ): bool {
        if ($invoice->paymentAllocations()->exists()) {
            return true;
        }

        if (
            DB::table('tenant_fund_transactions')
                ->where('invoice_id', $invoice->id)
                ->exists()
        ) {
            return true;
        }

        if (
            DB::table('security_deposit_applications')
                ->where('invoice_id', $invoice->id)
                ->exists()
        ) {
            return true;
        }

        if (
            DB::table('owner_transactions')
                ->where('invoice_id', $invoice->id)
                ->exists()
        ) {
            return true;
        }

        return false;
    }

    private function cancelIfUntouched(
        Invoice $invoice,
        Lease $lease,
        ?int $actorUserId
    ): void {
        if ($this->hasFinancialActivity($invoice)) {
            return;
        }

        $journalEntry = JournalEntry::query()
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->where('transaction_type', 'rent_invoice')
            ->where('entry_kind', JournalEntry::KIND_FINANCIAL)
            ->first();

        if (
            $journalEntry !== null
            && ! $journalEntry->isReversed()
        ) {
            $this->journalReversal->reverse(
                $journalEntry,
                sprintf(
                    'Rent Invoice %s cancelled because Lease %d terminates on %s.',
                    $invoice->invoice_number,
                    $lease->id,
                    $lease->termination_date->format('Y-m-d')
                ),
                $actorUserId
            );
        }

        $invoice->update([
            'status' => 'cancelled',
        ]);
    }
}
