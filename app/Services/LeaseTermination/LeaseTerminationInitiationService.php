<?php

namespace App\Services\LeaseTermination;

use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaseTerminationInitiationService
{
    public function __construct(
        private readonly LeaseTerminationStateService $state
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

                return $lease->refresh();
            }
        );
    }
}
