<?php

namespace App\Services\LeaseTermination;

use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaseTerminationInitiationService
{
    public function __construct(
        private readonly LeaseTerminationStateService $state,
        private readonly LeaseTerminationInvoiceService $invoices
    ) {}

    public function initiate(
        Lease $lease,
        string $noticeDate,
        string $terminationDate,
        string $finalRentMode
    ): Lease {
        return DB::transaction(
            function () use (
                $lease,
                $noticeDate,
                $terminationDate,
                $finalRentMode
            ): Lease {
                $lease = Lease::query()
                    ->whereKey($lease->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->state->canInitiate($lease)) {
                    throw new RuntimeException(
                        'Only an active Lease can enter the termination workflow.'
                    );
                }

                $this->state->assertValidFinalRentMode(
                    $finalRentMode
                );

                $previousStatus = $lease->status;

                $lease->update([
                    'status' => 'notice',
                    'termination_notice_date' => $noticeDate,
                    'termination_date' => $terminationDate,
                    'termination_final_rent_mode' => $finalRentMode,
                    'termination_previous_status' => $previousStatus,
                    'termination_completed_at' => null,
                ]);

                /*
                 * V1.0.5 Phase 9C:
                 *
                 * Entering termination notice immediately establishes the
                 * Lease billing boundary. Reconciliation is intentionally
                 * performed inside this same database transaction so the
                 * lifecycle change and safe Invoice cleanup are atomic.
                 *
                 * Financially touched Invoices remain preserved by the
                 * reconciliation service for explicit settlement/correction.
                 */
                $this->invoices->reconcile(
                    $lease->refresh(),
                    auth()->id()
                );

                return $lease->refresh();
            }
        );
    }
}
