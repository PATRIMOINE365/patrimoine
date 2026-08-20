<?php

namespace App\Services\LeaseTermination;

use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LeaseTerminationCancellationService
{
    public function __construct(
        private readonly LeaseTerminationStateService $state
    ) {}

    public function cancel(
        Lease $lease
    ): Lease {
        return DB::transaction(
            function () use ($lease): Lease {
                $lease = Lease::query()
                    ->whereKey($lease->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->state->canCancel($lease)) {
                    throw new RuntimeException(
                        'Only an in-progress Lease termination can be cancelled.'
                    );
                }

                $restorationStatus =
                    $this->state->restorationStatus(
                        $lease
                    );

                $lease->update([
                    'status' =>
                        $restorationStatus,

                    'termination_notice_date' =>
                        null,

                    'termination_date' =>
                        null,

                    'termination_final_rent_mode' =>
                        null,

                    'termination_previous_status' =>
                        null,

                    'termination_completed_at' =>
                        null,
                ]);

                /*
                 * Financial history created by termination initiation is
                 * intentionally not rewritten here.
                 *
                 * Cancelled Invoices remain cancelled and Journal reversals
                 * remain immutable. Normal Invoice generation may create
                 * replacement operational Invoices for missing periods.
                 */

                return $lease->refresh();
            }
        );
    }
}
