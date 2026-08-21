<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\LeaseTermination\LeaseTerminationStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LeaseTerminationStateFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_termination_lifecycle_fields_are_persisted_and_cast(): void
    {
        $lease =
            $this->lease([
                'status' => 'notice',
                'termination_notice_date' => '2026-08-10',
                'termination_date' => '2026-09-30',
                'termination_final_rent_mode' => 'prorate',
                'termination_previous_status' => 'active',
                'termination_completed_at' => '2026-10-01 08:30:00',
            ]);

        $lease->refresh();

        $this->assertSame(
            '2026-08-10',
            $lease->termination_notice_date?->toDateString()
        );

        $this->assertSame(
            '2026-09-30',
            $lease->termination_date?->toDateString()
        );

        $this->assertSame(
            'prorate',
            $lease->termination_final_rent_mode
        );

        $this->assertSame(
            'active',
            $lease->termination_previous_status
        );

        $this->assertSame(
            '2026-10-01 08:30:00',
            $lease->termination_completed_at?->format(
                'Y-m-d H:i:s'
            )
        );
    }

    public function test_active_lease_can_initiate_termination(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $this->assertTrue(
            $service->canInitiate(
                $this->lease([
                    'status' => 'active',
                ])
            )
        );

        foreach (
            [
                'draft',
                'notice',
                'terminated',
            ] as $status
        ) {
            $this->assertFalse(
                $service->canInitiate(
                    $this->lease([
                        'status' => $status,
                    ])
                )
            );
        }
    }

    public function test_notice_lease_with_required_metadata_is_in_progress_and_cancellable(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $lease =
            $this->lease([
                'status' => 'notice',
                'termination_notice_date' => '2026-08-10',
                'termination_date' => '2026-09-30',
                'termination_final_rent_mode' => 'full',
                'termination_previous_status' => 'active',
                'termination_completed_at' => null,
            ]);

        $this->assertTrue(
            $service->isInProgress(
                $lease
            )
        );

        $this->assertTrue(
            $service->canCancel(
                $lease
            )
        );
    }

    public function test_completed_termination_is_not_in_progress_or_cancellable(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $lease =
            $this->lease([
                'status' => 'terminated',
                'termination_notice_date' => '2026-08-10',
                'termination_date' => '2026-09-30',
                'termination_final_rent_mode' => 'none',
                'termination_previous_status' => 'active',
                'termination_completed_at' => '2026-09-30 12:00:00',
            ]);

        $this->assertFalse(
            $service->isInProgress(
                $lease
            )
        );

        $this->assertFalse(
            $service->canCancel(
                $lease
            )
        );
    }

    public function test_final_rent_modes_are_frozen(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $this->assertSame(
            [
                'prorate',
                'full',
                'none',
            ],
            $service->finalRentModes()
        );

        foreach (
            $service->finalRentModes() as $mode
        ) {
            $service->assertValidFinalRentMode(
                $mode
            );

            $this->addToAssertionCount(
                1
            );
        }

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->assertValidFinalRentMode(
            'automatic'
        );
    }

    public function test_cancellation_restoration_status_defaults_to_active(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $lease =
            $this->lease([
                'status' => 'notice',
                'termination_previous_status' => null,
            ]);

        $this->assertSame(
            'active',
            $service->restorationStatus(
                $lease
            )
        );

        $lease->termination_previous_status =
            'active';

        $this->assertSame(
            'active',
            $service->restorationStatus(
                $lease
            )
        );
    }

    public function test_invalid_stored_restoration_status_is_rejected(): void
    {
        $service =
            app(
                LeaseTerminationStateService::class
            );

        $lease =
            $this->lease([
                'status' => 'notice',
                'termination_previous_status' => 'terminated',
            ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->restorationStatus(
            $lease
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function lease(
        array $overrides = []
    ): Lease {
        $building =
            Building::create([
                'name' => 'Termination Foundation Building',
                'address' => 'Accra',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,
                'name' => 'Termination Foundation Unit',
            ]);

        $tenant =
            Party::create([
                'name' => 'Termination Foundation Tenant',
                'type' => 'person',
            ]);

        $tenant
            ->roles()
            ->create([
                'role' => 'tenant',
            ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'agent_id' => null,
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                    'status' => 'active',
                    'termination_notice_date' => null,
                    'termination_date' => null,
                    'termination_final_rent_mode' => null,
                    'termination_previous_status' => null,
                    'termination_completed_at' => null,
                    'rent_amount' => 3000,
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
                    'notes' => null,
                ],
                $overrides
            )
        );
    }
}
