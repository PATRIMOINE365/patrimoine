<?php

namespace App\Services\LeaseTermination;

use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LeaseTerminationCompletionService
{
    public function __construct(
        private readonly LeaseTerminationStateService $state,
        private readonly LeaseTerminationSettlementService $settlement
    ) {}

    public function complete(
        Lease $lease
    ): Lease {
        return DB::transaction(
            function () use ($lease): Lease {
                $lease = Lease::query()
                    ->whereKey($lease->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->state->isInProgress($lease)) {
                    throw new RuntimeException(
                        'Only a Lease under termination notice can complete termination.'
                    );
                }

                $summary =
                    $this->settlement->calculate(
                        $lease
                    );

                if (
                    ! $summary[
                        'settlement'
                    ][
                        'can_complete'
                    ]
                ) {
                    throw new RuntimeException(
                        'Lease termination cannot complete while settlement blockers remain.'
                    );
                }

                $lease->update([
                    'status' =>
                        'terminated',

                    'termination_completed_at' =>
                        now(),
                ]);

                return $lease->refresh();
            }
        );
    }
}
