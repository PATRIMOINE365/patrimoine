<?php

namespace App\Services\LeaseTermination;

use App\Models\Lease;
use InvalidArgumentException;

/**
 * Pure lifecycle-state rules for the V1.0.5 controlled
 * Lease termination workflow.
 *
 * This service deliberately does not:
 * - generate documents;
 * - mutate invoices;
 * - calculate settlement;
 * - post financial Journal entries;
 * - write Activity Log events.
 *
 * Those responsibilities are added in later Phase 9 checkpoints.
 */
class LeaseTerminationStateService
{
    public const FINAL_RENT_PRORATE = 'prorate';

    public const FINAL_RENT_FULL = 'full';

    public const FINAL_RENT_NONE = 'none';

    /**
     * @return list<string>
     */
    public function finalRentModes(): array
    {
        return [
            self::FINAL_RENT_PRORATE,
            self::FINAL_RENT_FULL,
            self::FINAL_RENT_NONE,
        ];
    }

    /**
     * Determine whether formal termination may be initiated.
     *
     * Draft Leases have not entered operational tenancy and therefore
     * should not use the normal termination workflow.
     *
     * A Lease already under notice or already terminated cannot initiate
     * termination again.
     */
    public function canInitiate(Lease $lease): bool
    {
        return $lease->status === 'active';
    }

    /**
     * Determine whether an in-progress termination may be cancelled.
     */
    public function canCancel(Lease $lease): bool
    {
        return $lease->status === 'notice'
            && $lease->termination_notice_date !== null
            && $lease->termination_date !== null
            && $lease->termination_completed_at === null;
    }

    /**
     * Determine whether the Lease currently represents
     * Termination in Progress.
     */
    public function isInProgress(Lease $lease): bool
    {
        return $lease->status === 'notice'
            && $lease->termination_completed_at === null;
    }

    /**
     * Validate the final-period billing choice.
     */
    public function assertValidFinalRentMode(
        string $mode
    ): void {
        if (
            ! in_array(
                $mode,
                $this->finalRentModes(),
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported termination final rent mode.'
            );
        }
    }

    /**
     * Return the persisted status that should be restored when
     * cancellation becomes available.
     *
     * The V1.0.5 controlled workflow currently initiates termination only
     * from an active Lease, but preserving this helper makes restoration
     * explicit and testable rather than relying on an implicit constant.
     */
    public function restorationStatus(
        Lease $lease
    ): string {
        $status =
            $lease->termination_previous_status;

        if ($status === null) {
            return 'active';
        }

        if (
            ! in_array(
                $status,
                [
                    'draft',
                    'active',
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Stored pre-termination Lease status is invalid.'
            );
        }

        return $status;
    }
}
