<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\LeaseTermination\LeaseTerminationCompletionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LeaseTerminationCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function lease(
        array $overrides = []
    ): Lease {
        $building =
            Building::create([
                'name' =>
                    'Termination Completion Building',

                'address' =>
                    'Accra',
            ]);

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Termination Completion Unit',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Termination Completion Tenant',

                'phone' =>
                    '0209000101',

                'email' =>
                    uniqid(
                        'phase9e1-',
                        true
                    ).'@example.test',
            ]);

        $tenant
            ->roles()
            ->create([
                'role' =>
                    'tenant',
            ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' =>
                        $unit->id,

                    'tenant_id' =>
                        $tenant->id,

                    'start_date' =>
                        '2026-01-01',

                    'end_date' =>
                        '2026-12-31',

                    'rent_amount' =>
                        5000,

                    'payment_frequency' =>
                        'monthly',

                    'due_day' =>
                        1,

                    'vat_rate' =>
                        0,

                    'status' =>
                        'notice',

                    'termination_notice_date' =>
                        '2026-08-01',

                    'termination_date' =>
                        '2026-08-31',

                    'termination_final_rent_mode' =>
                        'full',

                    'termination_previous_status' =>
                        'active',

                    'termination_completed_at' =>
                        null,

                    'security_deposit_amount' =>
                        0,

                    'advance_payment_amount' =>
                        0,

                    'rent_reserve_amount' =>
                        0,

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

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' =>
                $role,

            'is_active' =>
                true,
        ]);
    }

    public function test_resolved_termination_can_complete(): void
    {
        $lease =
            $this->lease();

        $completed =
            app(
                LeaseTerminationCompletionService::class
            )->complete(
                $lease
            );

        $this->assertSame(
            'terminated',
            $completed->status
        );

        $this->assertNotNull(
            $completed
                ->termination_completed_at
        );

        $this->assertSame(
            '2026-08-01',
            $completed
                ->termination_notice_date
                ?->toDateString()
        );

        $this->assertSame(
            '2026-08-31',
            $completed
                ->termination_date
                ?->toDateString()
        );

        $this->assertSame(
            'full',
            $completed
                ->termination_final_rent_mode
        );
    }

    public function test_outstanding_debt_blocks_completion(): void
    {
        $lease =
            $this->lease();

        Invoice::create([
            'lease_id' =>
                $lease->id,

            'invoice_number' =>
                'INV-9E1-DEBT',

            'type' =>
                'rent',

            'period_start' =>
                '2026-08-01',

            'period_end' =>
                '2026-08-31',

            'issue_date' =>
                '2026-08-01',

            'due_date' =>
                '2026-08-01',

            'status' =>
                'issued',

            'total_amount' =>
                5000,

            'vat_rate' =>
                0,

            'net_amount' =>
                5000,

            'vat_amount' =>
                0,
        ]);

        try {
            app(
                LeaseTerminationCompletionService::class
            )->complete(
                $lease
            );

            $this->fail(
                'Completion should have been blocked.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Lease termination cannot complete while settlement blockers remain.',
                $exception->getMessage()
            );
        }

        $lease->refresh();

        $this->assertSame(
            'notice',
            $lease->status
        );

        $this->assertNull(
            $lease
                ->termination_completed_at
        );
    }

    public function test_held_tenant_funds_block_completion(): void
    {
        $lease =
            $this->lease();

        $account =
            TenantFundAccount::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'rent_reserve',

                'status' =>
                    'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'reserve_funding',

            'amount' =>
                3000,

            'transaction_date' =>
                '2026-08-01',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            LeaseTerminationCompletionService::class
        )->complete(
            $lease
        );
    }

    public function test_active_lease_cannot_complete_termination(): void
    {
        $lease =
            $this->lease([
                'status' =>
                    'active',

                'termination_notice_date' =>
                    null,

                'termination_date' =>
                    null,

                'termination_final_rent_mode' =>
                    null,

                'termination_previous_status' =>
                    null,
            ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Only a Lease under termination notice can complete termination.'
        );

        app(
            LeaseTerminationCompletionService::class
        )->complete(
            $lease
        );
    }

    public function test_administrator_can_complete_through_api_and_activity_is_logged(): void
    {
        $lease =
            $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::Administrator
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/complete"
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'terminated'
            );

        $lease->refresh();

        $this->assertSame(
            'terminated',
            $lease->status
        );

        $this->assertNotNull(
            $lease
                ->termination_completed_at
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'lease.termination_completed',

                'entity_type' =>
                    'lease',

                'entity_id' =>
                    $lease->id,
            ]
        );
    }

    public function test_property_manager_can_complete_through_api(): void
    {
        $lease =
            $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::PropertyManager
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/complete"
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'terminated'
            );
    }

    public function test_viewer_cannot_complete_termination(): void
    {
        $lease =
            $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::Viewer
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/complete"
            )
            ->assertForbidden();

        $this->assertSame(
            'notice',
            $lease
                ->fresh()
                ->status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'lease.termination_completed',

                'entity_id' =>
                    $lease->id,
            ]
        );
    }

    public function test_api_returns_422_and_does_not_log_when_blockers_remain(): void
    {
        $lease =
            $this->lease();

        $account =
            TenantFundAccount::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'security_deposit',

                'status' =>
                    'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'deposit_funding',

            'amount' =>
                5000,

            'transaction_date' =>
                '2026-08-01',
        ]);

        $this
            ->actingAs(
                $this->user(
                    UserRole::Administrator
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/complete"
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Lease termination cannot complete while settlement blockers remain.'
            );

        $this->assertSame(
            'notice',
            $lease
                ->fresh()
                ->status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'lease.termination_completed',

                'entity_id' =>
                    $lease->id,
            ]
        );
    }

    public function test_completion_makes_unit_vacant_through_derived_occupancy(): void
    {
        $lease =
            $this->lease();

        $service =
            app(
                DashboardService::class
            );

        $asOf =
            Carbon::parse(
                '2026-08-20'
            );

        $this->assertSame(
            1,
            $service
                ->occupiedUnitCount(
                    $asOf
                )
        );

        app(
            LeaseTerminationCompletionService::class
        )->complete(
            $lease
        );

        $this->assertSame(
            0,
            $service
                ->occupiedUnitCount(
                    $asOf
                )
        );

        $this->assertSame(
            1,
            $service
                ->vacantUnitCount(
                    $asOf
                )
        );
    }
}
