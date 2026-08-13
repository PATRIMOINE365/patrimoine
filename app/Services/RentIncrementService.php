<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\RentIncrement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles Patrimoine rent-increment lifecycle rules.
 *
 * Important V1 rules:
 *
 * - rent increments are discretionary;
 * - Patrimoine never increases rent merely because a date has arrived;
 * - an increment must first be explicitly scheduled;
 * - increments may be percentage-based or fixed amounts;
 * - an increment cannot become effective less than 12 months after the
 *   Lease start date or the previous applied increment;
 * - the new rent is calculated when the increment is scheduled and stored
 *   as a historical snapshot;
 * - applying an increment updates the Lease's current contractual rent;
 * - after application, another increment cannot become effective for at
 *   least another 12 months;
 * - cancelled increments never change the Lease.
 *
 * Tenant notification is handled separately because notification delivery
 * is an operational concern rather than part of the rent calculation.
 */
class RentIncrementService
{
    /**
     * Minimum number of months for which an applied rent must remain in
     * force before another increment may become effective.
     */
    private const MINIMUM_INTERVAL_MONTHS = 12;

    /**
     * Schedule a future rent increment.
     *
     * Scheduling does not alter the Lease's current rent.
     *
     * @throws RuntimeException
     */
    public function schedule(
        Lease $lease,
        string $incrementType,
        float|string $incrementValue,
        string $effectiveDate
    ): RentIncrement {
        return DB::transaction(
            function () use (
                $lease,
                $incrementType,
                $incrementValue,
                $effectiveDate
            ): RentIncrement {
                /*
                 * Lock the Lease because rent-increment scheduling depends on
                 * its current rent and existing increment history.
                 */
                $lease = Lease::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lease->id
                    );

                $effective =
                    $this->parseDate(
                        $effectiveDate
                    );

                $this->validateLeaseStatus(
                    $lease
                );

                $this->validateIncrementType(
                    $incrementType
                );

                $numericValue =
                    (float) $incrementValue;

                $this->validateIncrementValue(
                    $incrementType,
                    $numericValue
                );

                $this->validateNoPendingIncrement(
                    $lease
                );

                $this->validateEffectiveDate(
                    $lease,
                    $effective
                );

                $oldRent =
                    (int) $lease->rent_amount;

                $newRent =
                    $this->calculateNewRent(
                        oldRent: $oldRent,
                        incrementType:
                            $incrementType,
                        incrementValue:
                            $numericValue
                    );

                if ($newRent <= $oldRent) {
                    throw new RuntimeException(
                        'A rent increment must increase the current rent amount.'
                    );
                }

                $increment =
                    RentIncrement::create([
                        'lease_id' =>
                            $lease->id,

                        'old_rent_amount' =>
                            $oldRent,

                        'increment_type' =>
                            $incrementType,

                        'increment_value' =>
                            $numericValue,

                        'new_rent_amount' =>
                            $newRent,

                        'effective_date' =>
                            $effective
                                ->toDateString(),

                        'status' =>
                            'scheduled',

                        'notification_sent_at' =>
                            null,

                        'applied_at' =>
                            null,

                        'cancelled_at' =>
                            null,
                    ]);

                /*
                 * This field represents the currently expected next rent
                 * increment date.
                 *
                 * It is informational and operational only. Its arrival does
                 * NOT independently authorize Patrimoine to change the rent.
                 * The scheduled RentIncrement record remains authoritative.
                 */
                $lease->update([
                    'next_rent_increment_date' =>
                        $effective
                            ->toDateString(),
                ]);

                return $increment->refresh();
            }
        );
    }

    /**
     * Apply a scheduled rent increment once its effective date arrives.
     *
     * This is the only operation in this service that changes Lease rent.
     *
     * @throws RuntimeException
     */
    public function apply(
        RentIncrement $increment,
        ?Carbon $asOfDate = null
    ): RentIncrement {
        return DB::transaction(
            function () use (
                $increment,
                $asOfDate
            ): RentIncrement {
                $increment =
                    RentIncrement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $increment->id
                        );

                $lease =
                    Lease::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $increment->lease_id
                        );

                if (
                    $increment->status
                    === 'applied'
                ) {
                    /*
                     * Idempotency protects commands from applying the same
                     * increment twice.
                     */
                    return $increment;
                }

                if (
                    $increment->status
                    === 'cancelled'
                ) {
                    throw new RuntimeException(
                        'A cancelled rent increment cannot be applied.'
                    );
                }

                if (
                    $increment->status
                    !== 'scheduled'
                ) {
                    throw new RuntimeException(
                        'Only a scheduled rent increment can be applied.'
                    );
                }

                $effectiveDate =
                    $increment
                        ->effective_date
                        ->copy()
                        ->startOfDay();

                $today =
                    ($asOfDate ?? now())
                        ->copy()
                        ->startOfDay();

                if (
                    $today->lt(
                        $effectiveDate
                    )
                ) {
                    throw new RuntimeException(
                        'The rent increment cannot be applied before its effective date.'
                    );
                }

                /*
                 * A scheduled increment is based on a snapshot of the rent
                 * that existed when it was approved.
                 *
                 * If the Lease rent changed independently in the meantime,
                 * silently applying the old calculation would corrupt the
                 * contractual history.
                 */
                if (
                    (int) $lease->rent_amount
                    !==
                    (int) $increment->old_rent_amount
                ) {
                    throw new RuntimeException(
                        'The Lease rent has changed since this increment was scheduled. Review the increment before applying it.'
                    );
                }

                $lease->update([
                    'rent_amount' =>
                        $increment
                            ->new_rent_amount,

                    /*
                     * This represents the earliest date on which another
                     * increment may become effective.
                     *
                     * Reaching this date does not automatically create or
                     * approve another increment.
                     */
                    'next_rent_increment_date' =>
                        $effectiveDate
                            ->copy()
                            ->addMonthsNoOverflow(
                                self::MINIMUM_INTERVAL_MONTHS
                            )
                            ->toDateString(),
                ]);

                $increment->update([
                    'status' =>
                        'applied',

                    'applied_at' =>
                        now(),
                ]);

                return $increment->refresh();
            }
        );
    }

    /**
     * Cancel an increment before it has been applied.
     *
     * Cancellation does not alter historical rent values.
     *
     * @throws RuntimeException
     */
    public function cancel(
        RentIncrement $increment
    ): RentIncrement {
        return DB::transaction(
            function () use (
                $increment
            ): RentIncrement {
                $increment =
                    RentIncrement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $increment->id
                        );

                if (
                    $increment->status
                    === 'applied'
                ) {
                    throw new RuntimeException(
                        'An applied rent increment cannot be cancelled.'
                    );
                }

                if (
                    $increment->status
                    === 'cancelled'
                ) {
                    return $increment;
                }

                if (
                    $increment->status
                    !== 'scheduled'
                ) {
                    throw new RuntimeException(
                        'Only a scheduled rent increment can be cancelled.'
                    );
                }

                $increment->update([
                    'status' =>
                        'cancelled',

                    'cancelled_at' =>
                        now(),
                ]);

                /*
                 * If this increment was the Lease's currently displayed
                 * upcoming date, remove it.
                 *
                 * A later scheduling operation may establish a new date.
                 */
                $lease =
                    Lease::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $increment->lease_id
                        );

                if (
                    $lease->next_rent_increment_date
                    !== null
                    && $lease
                        ->next_rent_increment_date
                        ->toDateString()
                        ===
                        $increment
                            ->effective_date
                            ->toDateString()
                ) {
                    $lease->update([
                        'next_rent_increment_date' =>
                            null,
                    ]);
                }

                return $increment->refresh();
            }
        );
    }

    /**
     * Mark the three-month tenant notification as successfully sent.
     *
     * The email service will call this only after delivery has been accepted.
     */
    public function markNotificationSent(
        RentIncrement $increment
    ): RentIncrement {
        return DB::transaction(
            function () use (
                $increment
            ): RentIncrement {
                $increment =
                    RentIncrement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $increment->id
                        );

                if (
                    $increment->status
                    !== 'scheduled'
                ) {
                    throw new RuntimeException(
                        'Only a scheduled rent increment can be notified.'
                    );
                }

                if (
                    $increment
                        ->notification_sent_at
                    !== null
                ) {
                    return $increment;
                }

                $increment->update([
                    'notification_sent_at' =>
                        now(),
                ]);

                return $increment->refresh();
            }
        );
    }

    /**
     * Calculate the new whole-currency rent.
     *
     * Patrimoine V1 stores monetary values as integers.
     *
     * Percentage increments are therefore rounded to the nearest whole
     * currency unit.
     *
     * @throws RuntimeException
     */
    public function calculateNewRent(
        int $oldRent,
        string $incrementType,
        float|string $incrementValue
    ): int {
        $this->validateIncrementType(
            $incrementType
        );

        $numericValue =
            (float) $incrementValue;

        $this->validateIncrementValue(
            $incrementType,
            $numericValue
        );

        if ($oldRent < 0) {
            throw new RuntimeException(
                'Current rent cannot be negative.'
            );
        }

        return match (
            $incrementType
        ) {
            'percentage' =>
                (int) round(
                    $oldRent
                    * (
                        1
                        + (
                            $numericValue
                            / 100
                        )
                    )
                ),

            'fixed' =>
                $oldRent
                + (int) round(
                    $numericValue
                ),

            default =>
                throw new RuntimeException(
                    'Unsupported rent increment type.'
                ),
        };
    }

    /**
     * Return the earliest effective date permitted for the next increment.
     *
     * The first increment cannot become effective until the Lease has run
     * for at least 12 months.
     *
     * Afterward, the calculation uses the latest applied increment.
     */
    public function earliestNextEffectiveDate(
        Lease $lease
    ): Carbon {
        $latestApplied =
            RentIncrement::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->where(
                    'status',
                    'applied'
                )
                ->orderByDesc(
                    'effective_date'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();

        if ($latestApplied !== null) {
            return $latestApplied
                ->effective_date
                ->copy()
                ->addMonthsNoOverflow(
                    self::MINIMUM_INTERVAL_MONTHS
                )
                ->startOfDay();
        }

        return $lease
            ->start_date
            ->copy()
            ->addMonthsNoOverflow(
                self::MINIMUM_INTERVAL_MONTHS
            )
            ->startOfDay();
    }

    /**
     * Ensure the Lease is in a state where rent can meaningfully change.
     */
    private function validateLeaseStatus(
        Lease $lease
    ): void {
        if (
            ! in_array(
                $lease->status,
                [
                    'active',
                    'notice',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Rent increments can only be scheduled for an active or notice Lease.'
            );
        }
    }

    /**
     * Validate supported V1 rent increment methods.
     */
    private function validateIncrementType(
        string $incrementType
    ): void {
        if (
            ! in_array(
                $incrementType,
                [
                    'percentage',
                    'fixed',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Rent increment type must be percentage or fixed.'
            );
        }
    }

    /**
     * Validate the configured increment value.
     */
    private function validateIncrementValue(
        string $incrementType,
        float $incrementValue
    ): void {
        if (
            ! is_finite(
                $incrementValue
            )
            || $incrementValue <= 0
        ) {
            throw new RuntimeException(
                'Rent increment value must be greater than zero.'
            );
        }

        if (
            $incrementType
            === 'percentage'
            && $incrementValue > 100
        ) {
            throw new RuntimeException(
                'Percentage rent increment cannot exceed 100%.'
            );
        }
    }

    /**
     * A Lease may have only one outstanding approved increment at a time.
     */
    private function validateNoPendingIncrement(
        Lease $lease
    ): void {
        $exists =
            RentIncrement::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->where(
                    'status',
                    'scheduled'
                )
                ->exists();

        if ($exists) {
            throw new RuntimeException(
                'This Lease already has a scheduled rent increment.'
            );
        }
    }

    /**
     * Validate the yearly minimum interval.
     */
    private function validateEffectiveDate(
        Lease $lease,
        Carbon $effectiveDate
    ): void {
        $earliest =
            $this
                ->earliestNextEffectiveDate(
                    $lease
                );

        if (
            $effectiveDate->lt(
                $earliest
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'The next rent increment cannot become effective before %s.',
                    $earliest
                        ->toDateString()
                )
            );
        }
    }

    /**
     * Parse an exact YYYY-MM-DD date.
     *
     * @throws RuntimeException
     */
    private function parseDate(
        string $value
    ): Carbon {
        try {
            $date =
                Carbon::createFromFormat(
                    '!Y-m-d',
                    $value
                );
        } catch (\Throwable) {
            throw new RuntimeException(
                'Rent increment effective date must use YYYY-MM-DD format.'
            );
        }

        if (
            $date === false
            || $date
                ->format('Y-m-d')
                !== $value
        ) {
            throw new RuntimeException(
                'Rent increment effective date must use YYYY-MM-DD format.'
            );
        }

        return $date->startOfDay();
    }
}
