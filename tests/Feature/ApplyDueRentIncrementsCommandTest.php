<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\RentIncrement;
use App\Models\Unit;
use App\Services\RentIncrementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies automatic application of approved rent increments.
 */
class ApplyDueRentIncrementsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an active Lease suitable for automatic increment processing.
     *
     * @param array<string, mixed> $overrides
     */
    private function createLease(
        string $suffix,
        array $overrides = []
    ): Lease {
        $tenant = Party::create([
            'type' =>
                'person',

            'name' =>
                'Automatic Increment Tenant '.$suffix,

            'phone' =>
                '0200092'.$suffix,

            'email' =>
                'automatic-increment-'.$suffix.'@example.test',
        ]);

        $tenant->roles()->create([
            'role' =>
                'tenant',
        ]);

        $building = Building::create([
            'name' =>
                'Automatic Increment Building '.$suffix,
        ]);

        $unit = Unit::create([
            'building_id' =>
                $building->id,

            'name' =>
                'Automatic Increment Unit '.$suffix,
        ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' =>
                        $unit->id,

                    'tenant_id' =>
                        $tenant->id,

                    'start_date' =>
                        '2025-01-01',

                    'status' =>
                        'active',

                    'rent_amount' =>
                        10000,

                    'payment_frequency' =>
                        'monthly',

                    'due_day' =>
                        1,

                    'vat_rate' =>
                        18,

                    'proration_amount' =>
                        null,

                    'security_deposit_amount' =>
                        0,

                    'advance_payment_amount' =>
                        0,

                    'rent_reserve_amount' =>
                        0,

                    'rent_increment_type' =>
                        'none',

                    'rent_increment_value' =>
                        0,

                    'next_rent_increment_date' =>
                        null,

                    'management_fee_type' =>
                        'none',

                    'management_fee_value' =>
                        0,

                    'agent_commission_amount' =>
                        0,
                ],
                $overrides
            )
        );
    }

    /**
     * Schedule a standard percentage increment.
     */
    private function scheduleIncrement(
        Lease $lease,
        string $effectiveDate
    ): RentIncrement {
        return app(
            RentIncrementService::class
        )->schedule(
            lease:
                $lease,

            incrementType:
                'percentage',

            incrementValue:
                10,

            effectiveDate:
                $effectiveDate
        );
    }

    /**
     * A due scheduled increment updates current contractual rent.
     */
    public function test_command_applies_due_increment(): void
    {
        $lease =
            $this->createLease(
                '001'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2026-08-15'
            );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-15',
            ]
        )
            ->expectsOutput(
                'Rent increment #'
                .$increment->id
                .' for Lease #'
                .$lease->id
                .' applied.'
            )
            ->assertSuccessful();

        $lease =
            $lease->fresh();

        $increment =
            $increment->fresh();

        $this->assertSame(
            11000,
            $lease->rent_amount
        );

        $this->assertSame(
            'applied',
            $increment->status
        );

        $this->assertNotNull(
            $increment->applied_at
        );
    }

    /**
     * Applying an increment advances the earliest next increment date.
     */
    public function test_command_advances_next_increment_date(): void
    {
        $lease =
            $this->createLease(
                '002'
            );

        $this->scheduleIncrement(
            $lease,
            '2026-08-15'
        );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-15',
            ]
        )->assertSuccessful();

        $this->assertSame(
            '2027-08-15',
            $lease
                ->fresh()
                ->next_rent_increment_date
                ->toDateString()
        );
    }

    /**
     * A future increment must remain scheduled until its effective date.
     */
    public function test_command_does_not_apply_increment_early(): void
    {
        $lease =
            $this->createLease(
                '003'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2026-08-15'
            );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-14',
            ]
        )->assertSuccessful();

        $this->assertSame(
            10000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'scheduled',
            $increment
                ->fresh()
                ->status
        );
    }

    /**
     * A missed daily run applies an outstanding increment on the next run.
     */
    public function test_overdue_increment_is_applied_on_later_run(): void
    {
        $lease =
            $this->createLease(
                '004'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2026-08-15'
            );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-20',
            ]
        )->assertSuccessful();

        $this->assertSame(
            11000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'applied',
            $increment
                ->fresh()
                ->status
        );
    }

    /**
     * Re-running automatic processing cannot increase the rent twice.
     */
    public function test_command_is_idempotent(): void
    {
        $lease =
            $this->createLease(
                '005'
            );

        $this->scheduleIncrement(
            $lease,
            '2026-08-15'
        );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-15',
            ]
        )->assertSuccessful();

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-16',
            ]
        )->assertSuccessful();

        $this->assertSame(
            11000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertDatabaseCount(
            'rent_increments',
            1
        );
    }

    /**
     * Cancelled increments are excluded from automatic application.
     */
    public function test_cancelled_increment_is_not_applied(): void
    {
        $lease =
            $this->createLease(
                '006'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2026-08-15'
            );

        app(
            RentIncrementService::class
        )->cancel(
            $increment
        );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-15',
            ]
        )->assertSuccessful();

        $this->assertSame(
            10000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'cancelled',
            $increment
                ->fresh()
                ->status
        );
    }

    /**
     * One invalid increment does not prevent another due increment applying.
     */
    public function test_failed_increment_does_not_stop_other_due_increments(): void
    {
        $failedLease =
            $this->createLease(
                '007'
            );

        $successfulLease =
            $this->createLease(
                '008'
            );

        $failedIncrement =
            $this->scheduleIncrement(
                $failedLease,
                '2026-08-15'
            );

        $successfulIncrement =
            $this->scheduleIncrement(
                $successfulLease,
                '2026-08-15'
            );

        /*
         * Simulate a manual contractual change after the first increment
         * was approved. The service must refuse to overwrite that newer rent.
         */
        $failedLease->update([
            'rent_amount' =>
                12000,
        ]);

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-08-15',
            ]
        )->assertFailed();

        $this->assertSame(
            12000,
            $failedLease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'scheduled',
            $failedIncrement
                ->fresh()
                ->status
        );

        $this->assertSame(
            11000,
            $successfulLease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'applied',
            $successfulIncrement
                ->fresh()
                ->status
        );
    }

    /**
     * Invalid administrative processing dates are rejected.
     */
    public function test_command_rejects_invalid_as_of_date(): void
    {
        $lease =
            $this->createLease(
                '009'
            );

        $this->scheduleIncrement(
            $lease,
            '2026-08-15'
        );

        $this->artisan(
            'patrimoine:apply-due-rent-increments',
            [
                '--as-of' =>
                    '2026-02-30',
            ]
        )
            ->expectsOutput(
                'The --as-of option must be a valid date in YYYY-MM-DD format.'
            )
            ->assertFailed();

        $this->assertSame(
            10000,
            $lease
                ->fresh()
                ->rent_amount
        );
    }
}
