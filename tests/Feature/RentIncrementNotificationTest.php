<?php

namespace Tests\Feature;

use App\Mail\RentIncrementNoticeMail;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\RentIncrement;
use App\Models\Unit;
use App\Services\RentIncrementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Verifies advance tenant notifications for scheduled rent increments.
 *
 * Patrimoine V1 provides three calendar months of advance notice.
 * Outstanding notices remain eligible until the day before the increment
 * becomes effective, while successfully sent notices are never duplicated.
 */
class RentIncrementNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an active Lease suitable for notification tests.
     */
    private function createLease(
        string $tenantEmail = 'rent-notice@example.test',
        array $overrides = []
    ): Lease {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Rent Notice Tenant',
            'phone' => '0200091001',
            'email' => $tenantEmail,
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $building = Building::create([
            'name' => 'Rent Notice Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Rent Notice Unit',
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
     * Schedule a standard percentage increment for a Lease.
     */
    private function scheduleIncrement(
        Lease $lease,
        string $effectiveDate
    ): RentIncrement {
        return app(
            RentIncrementService::class
        )->schedule(
            lease: $lease,
            incrementType:
                'percentage',
            incrementValue:
                10,
            effectiveDate:
                $effectiveDate
        );
    }

    /**
     * Notice is sent exactly three calendar months before effective date.
     */
    public function test_notice_is_sent_three_months_before_effective_date(): void
    {
        Mail::fake();

        $lease =
            $this->createLease(
                'exact-date@example.test'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-15',
            ]
        )->assertSuccessful();

        Mail::assertSent(
            RentIncrementNoticeMail::class,
            function (
                RentIncrementNoticeMail $mail
            ): bool {
                return $mail->hasTo(
                    'exact-date@example.test'
                );
            }
        );

        $this->assertNotNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * A missed notification remains outstanding before effective date.
     */
    public function test_missed_notice_is_sent_on_later_run(): void
    {
        Mail::fake();

        $lease =
            $this->createLease(
                'late-notice@example.test'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        /*
         * The exact notification date was 2027-05-15.
         * A later successful run must still send the outstanding notice.
         */
        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-06-01',
            ]
        )->assertSuccessful();

        Mail::assertSent(
            RentIncrementNoticeMail::class,
            function (
                RentIncrementNoticeMail $mail
            ): bool {
                return $mail->hasTo(
                    'late-notice@example.test'
                );
            }
        );

        $this->assertNotNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * Increment outside the three-month notice window is not notified.
     */
    public function test_future_increment_outside_notice_window_is_skipped(): void
    {
        Mail::fake();

        $lease =
            $this->createLease();

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-14',
            ]
        )->assertSuccessful();

        Mail::assertNothingSent();

        $this->assertNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * Successfully notified increment is never emailed twice.
     */
    public function test_already_notified_increment_is_skipped(): void
    {
        Mail::fake();

        $lease =
            $this->createLease();

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        app(
            RentIncrementService::class
        )->markNotificationSent(
            $increment
        );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-15',
            ]
        )->assertSuccessful();

        Mail::assertNothingSent();
    }

    /**
     * Cancelled increments must never produce tenant notices.
     */
    public function test_cancelled_increment_is_skipped(): void
    {
        Mail::fake();

        $lease =
            $this->createLease();

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        app(
            RentIncrementService::class
        )->cancel(
            $increment
        );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-15',
            ]
        )->assertSuccessful();

        Mail::assertNothingSent();

        $this->assertNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * Once the effective date arrives, advance notice is no longer sent.
     */
    public function test_notice_is_not_sent_on_effective_date(): void
    {
        Mail::fake();

        $lease =
            $this->createLease();

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-08-15',
            ]
        )->assertSuccessful();

        Mail::assertNothingSent();

        $this->assertNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * The final day before the increment takes effect remains eligible.
     */
    public function test_notice_can_be_sent_day_before_effective_date(): void
    {
        Mail::fake();

        $lease =
            $this->createLease(
                'last-day@example.test'
            );

        $increment =
            $this->scheduleIncrement(
                $lease,
                '2027-08-15'
            );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-08-14',
            ]
        )->assertSuccessful();

        Mail::assertSent(
            RentIncrementNoticeMail::class,
            function (
                RentIncrementNoticeMail $mail
            ): bool {
                return $mail->hasTo(
                    'last-day@example.test'
                );
            }
        );

        $this->assertNotNull(
            $increment
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * Multiple eligible increments are processed during the same run.
     */
    public function test_multiple_due_notices_are_sent_in_same_run(): void
    {
        Mail::fake();

        $firstLease =
            $this->createLease(
                'tenant-one@example.test'
            );

        $secondLease =
            $this->createLease(
                'tenant-two@example.test'
            );

        $firstIncrement =
            $this->scheduleIncrement(
                $firstLease,
                '2027-08-15'
            );

        $secondIncrement =
            $this->scheduleIncrement(
                $secondLease,
                '2027-08-20'
            );

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-20',
            ]
        )->assertSuccessful();

        Mail::assertSent(
            RentIncrementNoticeMail::class,
            2
        );

        $this->assertNotNull(
            $firstIncrement
                ->fresh()
                ->notification_sent_at
        );

        $this->assertNotNull(
            $secondIncrement
                ->fresh()
                ->notification_sent_at
        );
    }

    /**
     * Invalid administrative processing dates must be rejected.
     */
    public function test_command_rejects_invalid_as_of_date(): void
    {
        Mail::fake();

        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-02-30',
            ]
        )
            ->expectsOutput(
                'The --as-of option must be a valid date in YYYY-MM-DD format.'
            )
            ->assertFailed();

        Mail::assertNothingSent();
    }

    /**
     * A failed email remains outstanding and does not stop later notices.
     */
    public function test_failed_notice_does_not_stop_other_tenants(): void
    {
        $firstLease =
            $this->createLease(
                'failed-notice@example.test'
            );

        $secondLease =
            $this->createLease(
                'successful-notice@example.test'
            );

        $firstIncrement =
            $this->scheduleIncrement(
                $firstLease,
                '2027-08-15'
            );

        $secondIncrement =
            $this->scheduleIncrement(
                $secondLease,
                '2027-08-15'
            );

        /*
         * Replace only the delivery service for this test.
         *
         * The first tenant simulates an email transport failure. The second
         * delivery succeeds, proving that one failure does not abort the
         * remainder of the notification run.
         */
        $delivery =
            \Mockery::mock(
                \App\Services\Notifications\EmailDeliveryService::class
            );

        $attempt =
            0;

        $delivery
            ->shouldReceive(
                'sendRentIncrementNotice'
            )
            ->twice()
            ->andReturnUsing(
                function () use (
                    &$attempt
                ): void {
                    $attempt++;

                    if ($attempt === 1) {
                        throw new \RuntimeException(
                            'Simulated email delivery failure.'
                        );
                    }
                }
            );

        $this->app->instance(
            \App\Services\Notifications\EmailDeliveryService::class,
            $delivery
        );

        /*
         * The overall command reports failure because at least one notice
         * failed, even though subsequent eligible notices are still sent.
         */
        $this->artisan(
            'patrimoine:send-rent-increment-notices',
            [
                '--as-of' =>
                    '2027-05-15',
            ]
        )->assertFailed();

        /*
         * Failed delivery must remain eligible for a future retry.
         */
        $this->assertNull(
            $firstIncrement
                ->fresh()
                ->notification_sent_at
        );

        /*
         * The second tenant was processed successfully despite the first
         * tenant's failure.
         */
        $this->assertNotNull(
            $secondIncrement
                ->fresh()
                ->notification_sent_at
        );
    }

}
