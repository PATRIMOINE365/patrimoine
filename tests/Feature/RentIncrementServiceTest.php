<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\RentIncrementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine discretionary yearly rent-increment processing.
 */
class RentIncrementServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an active Lease suitable for rent-increment tests.
     */
    private function createLease(
        array $overrides = []
    ): Lease {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Increment Tenant',
            'phone' => '0200090001',
            'email' => 'increment-tenant@example.test',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $building = Building::create([
            'name' => 'Increment Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Increment Unit',
        ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' => $unit->id,

                    'tenant_id' => $tenant->id,

                    'start_date' => '2025-01-01',

                    'status' => 'active',

                    'rent_amount' => 10000,

                    'payment_frequency' => 'monthly',

                    'due_day' => 1,

                    'vat_rate' => 18,

                    'proration_amount' => null,

                    'security_deposit_amount' => 0,

                    'advance_payment_amount' => 0,

                    'rent_reserve_amount' => 0,

                    'rent_increment_type' => 'none',

                    'rent_increment_value' => 0,

                    'next_rent_increment_date' => null,

                    'management_fee_type' => 'none',

                    'management_fee_value' => 0,

                    'agent_commission_amount' => 0,
                ],
                $overrides
            )
        );
    }

    /**
     * Percentage increments calculate a new whole-currency rent.
     */
    public function test_percentage_increment_calculates_new_rent(): void
    {
        $service =
            app(
                RentIncrementService::class
            );

        $this->assertSame(
            11000,
            $service->calculateNewRent(
                10000,
                'percentage',
                10
            )
        );
    }

    /**
     * Fixed increments add the configured amount to the existing rent.
     */
    public function test_fixed_increment_calculates_new_rent(): void
    {
        $service =
            app(
                RentIncrementService::class
            );

        $this->assertSame(
            11500,
            $service->calculateNewRent(
                10000,
                'fixed',
                1500
            )
        );
    }

    /**
     * An increment must be explicitly scheduled.
     *
     * Merely reaching a Lease increment date must never change rent.
     */
    public function test_scheduling_increment_does_not_change_current_rent(): void
    {
        $lease =
            $this->createLease();

        $increment =
            app(
                RentIncrementService::class
            )->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $this->assertSame(
            10000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'scheduled',
            $increment->status
        );

        $this->assertSame(
            10000,
            $increment->old_rent_amount
        );

        $this->assertSame(
            11000,
            $increment->new_rent_amount
        );

        $this->assertSame(
            '2026-01-01',
            $increment
                ->effective_date
                ->toDateString()
        );
    }

    /**
     * First increment cannot become effective before 12 months of rent.
     */
    public function test_first_increment_requires_minimum_twelve_month_interval(): void
    {
        $lease =
            $this->createLease([
                'start_date' => '2025-01-15',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'The next rent increment cannot become effective before 2026-01-15.'
        );

        app(
            RentIncrementService::class
        )->schedule(
            lease: $lease,
            incrementType: 'percentage',
            incrementValue: 10,
            effectiveDate: '2026-01-14'
        );
    }

    /**
     * Exactly 12 months after Lease commencement is allowed.
     */
    public function test_first_increment_is_allowed_after_twelve_months(): void
    {
        $lease =
            $this->createLease([
                'start_date' => '2025-01-15',
            ]);

        $increment =
            app(
                RentIncrementService::class
            )->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-15'
            );

        $this->assertSame(
            'scheduled',
            $increment->status
        );
    }

    /**
     * Only one scheduled increment may exist for a Lease.
     */
    public function test_lease_cannot_have_multiple_scheduled_increments(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $service->schedule(
            lease: $lease,
            incrementType: 'percentage',
            incrementValue: 10,
            effectiveDate: '2026-01-01'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'This Lease already has a scheduled rent increment.'
        );

        $service->schedule(
            lease: $lease,
            incrementType: 'fixed',
            incrementValue: 1000,
            effectiveDate: '2027-01-01'
        );
    }

    /**
     * Scheduled increment cannot be applied before its effective date.
     */
    public function test_increment_cannot_be_applied_early(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'The rent increment cannot be applied before its effective date.'
        );

        $service->apply(
            $increment,
            Carbon::parse(
                '2025-12-31'
            )
        );
    }

    /**
     * Applying the increment changes current rent on the effective date.
     */
    public function test_scheduled_increment_can_be_applied_on_effective_date(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $applied =
            $service->apply(
                $increment,
                Carbon::parse(
                    '2026-01-01'
                )
            );

        $this->assertSame(
            11000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertSame(
            'applied',
            $applied->status
        );

        $this->assertNotNull(
            $applied->applied_at
        );

        /*
         * Another increment may not become effective for at least
         * another 12 months.
         */
        $this->assertSame(
            '2027-01-01',
            $lease
                ->fresh()
                ->next_rent_increment_date
                ->toDateString()
        );
    }

    /**
     * Re-running application is harmless and does not increase rent twice.
     */
    public function test_applying_increment_is_idempotent(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'fixed',
                incrementValue: 1500,
                effectiveDate: '2026-01-01'
            );

        $service->apply(
            $increment,
            Carbon::parse(
                '2026-01-01'
            )
        );

        $service->apply(
            $increment->fresh(),
            Carbon::parse(
                '2026-01-02'
            )
        );

        $this->assertSame(
            11500,
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
     * After an applied increment, the next one must wait another 12 months.
     */
    public function test_next_increment_requires_another_twelve_months(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $first =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $service->apply(
            $first,
            Carbon::parse(
                '2026-01-01'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'The next rent increment cannot become effective before 2027-01-01.'
        );

        $service->schedule(
            lease: $lease->fresh(),
            incrementType: 'percentage',
            incrementValue: 5,
            effectiveDate: '2026-12-31'
        );
    }

    /**
     * The managing organisation may decide not to proceed with an increment.
     */
    public function test_scheduled_increment_can_be_cancelled(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $cancelled =
            $service->cancel(
                $increment
            );

        $this->assertSame(
            'cancelled',
            $cancelled->status
        );

        $this->assertNotNull(
            $cancelled->cancelled_at
        );

        $this->assertSame(
            10000,
            $lease
                ->fresh()
                ->rent_amount
        );

        $this->assertNull(
            $lease
                ->fresh()
                ->next_rent_increment_date
        );
    }

    /**
     * A cancelled increment must never be applied.
     */
    public function test_cancelled_increment_cannot_be_applied(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $service->cancel(
            $increment
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'A cancelled rent increment cannot be applied.'
        );

        $service->apply(
            $increment->fresh(),
            Carbon::parse(
                '2026-01-01'
            )
        );
    }

    /**
     * A scheduled increment must not silently overwrite a later manual
     * contractual rent change.
     */
    public function test_increment_rejects_changed_rent_snapshot(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $lease->update([
            'rent_amount' => 12000,
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'The Lease rent has changed since this increment was scheduled. Review the increment before applying it.'
        );

        $service->apply(
            $increment,
            Carbon::parse(
                '2026-01-01'
            )
        );
    }

    /**
     * Tenant notification may only be recorded once for a scheduled increment.
     */
    public function test_increment_notification_can_be_marked_sent_once(): void
    {
        $lease =
            $this->createLease();

        $service =
            app(
                RentIncrementService::class
            );

        $increment =
            $service->schedule(
                lease: $lease,
                incrementType: 'percentage',
                incrementValue: 10,
                effectiveDate: '2026-01-01'
            );

        $first =
            $service
                ->markNotificationSent(
                    $increment
                );

        $sentAt =
            $first
                ->notification_sent_at
                ->toDateTimeString();

        $second =
            $service
                ->markNotificationSent(
                    $first
                );

        $this->assertSame(
            $sentAt,
            $second
                ->notification_sent_at
                ->toDateTimeString()
        );
    }
}
