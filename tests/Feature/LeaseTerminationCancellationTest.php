<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use App\Services\LeaseTermination\LeaseTerminationCancellationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LeaseTerminationCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function lease(
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' => 'Termination Cancellation Building',
            'address' => 'Accra',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Termination Cancellation Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Termination Cancellation Tenant',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                    'status' => 'notice',
                    'termination_notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'termination_final_rent_mode' => 'full',
                    'termination_previous_status' => 'active',
                    'termination_completed_at' => null,
                    'rent_amount' => 3000,
                    'payment_frequency' => 'monthly',
                    'due_day' => 1,
                    'vat_rate' => 0,
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

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_in_progress_termination_can_be_cancelled(): void
    {
        $lease = $this->lease();

        $restored =
            app(
                LeaseTerminationCancellationService::class
            )->cancel(
                $lease
            );

        $this->assertSame(
            'active',
            $restored->status
        );

        $this->assertNull(
            $restored->termination_notice_date
        );

        $this->assertNull(
            $restored->termination_date
        );

        $this->assertNull(
            $restored->termination_final_rent_mode
        );

        $this->assertNull(
            $restored->termination_previous_status
        );

        $this->assertNull(
            $restored->termination_completed_at
        );
    }

    public function test_completed_termination_cannot_be_cancelled(): void
    {
        $lease = $this->lease([
            'status' => 'terminated',
            'termination_completed_at' =>
                '2026-09-30 12:00:00',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            LeaseTerminationCancellationService::class
        )->cancel(
            $lease
        );
    }

    public function test_cancel_preserves_cancelled_invoice_history(): void
    {
        $lease = $this->lease();

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-9E2-CANCELLED',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-01',
            'status' => 'cancelled',
            'total_amount' => 3000,
            'vat_rate' => 0,
            'net_amount' => 3000,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        app(
            LeaseTerminationCancellationService::class
        )->cancel(
            $lease
        );

        $this->assertSame(
            'cancelled',
            $invoice->fresh()->status
        );
    }

    public function test_cancelled_period_can_receive_new_operational_invoice(): void
    {
        $lease = $this->lease();

        Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-9E2-HISTORY',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-01',
            'status' => 'cancelled',
            'total_amount' => 3000,
            'vat_rate' => 0,
            'net_amount' => 3000,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        $lease =
            app(
                LeaseTerminationCancellationService::class
            )->cancel(
                $lease
            );

        app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-10-01')
        );

        $this->assertSame(
            2,
            Invoice::query()
                ->where('lease_id', $lease->id)
                ->whereDate(
                    'period_start',
                    '2026-10-01'
                )
                ->count()
        );

        $this->assertSame(
            1,
            Invoice::query()
                ->where('lease_id', $lease->id)
                ->whereDate(
                    'period_start',
                    '2026-10-01'
                )
                ->where('status', 'issued')
                ->count()
        );
    }

    public function test_administrator_can_cancel_through_api_and_activity_is_logged(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::Administrator
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/cancel"
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'active'
            );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'lease.termination_cancelled',

                'entity_type' =>
                    'lease',

                'entity_id' =>
                    $lease->id,
            ]
        );
    }

    public function test_property_manager_can_cancel_through_api(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::PropertyManager
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/cancel"
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'active'
            );
    }

    public function test_viewer_cannot_cancel_termination(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs(
                $this->user(
                    UserRole::Viewer
                )
            )
            ->postJson(
                "/api/leases/{$lease->id}/termination/cancel"
            )
            ->assertForbidden();

        $this->assertSame(
            'notice',
            $lease->fresh()->status
        );
    }
}
