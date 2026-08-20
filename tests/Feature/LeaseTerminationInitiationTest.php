<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseTerminationInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_lease_can_enter_controlled_termination(): void
    {
        $user = $this->propertyManager();
        $lease = $this->lease();

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'prorate',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'notice')
            ->assertJsonPath(
                'termination_notice_date',
                '2026-08-20T00:00:00.000000Z'
            )
            ->assertJsonPath(
                'termination_date',
                '2026-09-30T00:00:00.000000Z'
            )
            ->assertJsonPath(
                'termination_final_rent_mode',
                'prorate'
            )
            ->assertJsonPath(
                'termination_previous_status',
                'active'
            );

        $this->assertDatabaseHas(
            'leases',
            [
                'id' => $lease->id,
                'status' => 'notice',
                'termination_notice_date' => '2026-08-20 00:00:00',
                'termination_date' => '2026-09-30 00:00:00',
                'termination_final_rent_mode' => 'prorate',
                'termination_previous_status' => 'active',
                'termination_completed_at' => null,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'lease.termination_initiated',
                'entity_type' => 'lease',
                'entity_id' => $lease->id,
            ]
        );
    }

    public function test_administrator_can_initiate_termination(): void
    {
        $lease = $this->lease();

        $administrator = User::factory()->create([
            'role' => UserRole::Administrator,
        ]);

        $this
            ->actingAs($administrator)
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'full',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'notice'
            );

        $this->assertSame(
            'notice',
            $lease->fresh()->status
        );
    }

    public function test_notice_lease_cannot_initiate_termination_again(): void
    {
        $lease = $this->lease([
            'status' => 'notice',
            'termination_notice_date' => '2026-08-01',
            'termination_date' => '2026-09-01',
            'termination_final_rent_mode' => 'full',
            'termination_previous_status' => 'active',
        ]);

        $this
            ->actingAs($this->propertyManager())
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'prorate',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'lease',
            ]);
    }

    public function test_draft_and_terminated_leases_cannot_initiate_termination(): void
    {
        foreach (['draft', 'terminated'] as $status) {
            $lease = $this->lease([
                'status' => $status,
            ]);

            $this
                ->actingAs($this->propertyManager())
                ->postJson(
                    "/api/leases/{$lease->id}/termination",
                    [
                        'notice_date' => '2026-08-20',
                        'termination_date' => '2026-09-30',
                        'final_rent_mode' => 'full',
                    ]
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'lease',
                ]);
        }
    }

    public function test_termination_date_cannot_precede_notice_date(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs($this->propertyManager())
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-09-30',
                    'termination_date' => '2026-09-01',
                    'final_rent_mode' => 'prorate',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'termination_date',
            ]);

        $this->assertSame(
            'active',
            $lease->fresh()->status
        );
    }

    public function test_final_rent_mode_is_restricted_to_frozen_modes(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs($this->propertyManager())
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'automatic',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'final_rent_mode',
            ]);
    }

    public function test_viewer_cannot_initiate_termination(): void
    {
        $lease = $this->lease();

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
        ]);

        $this
            ->actingAs($viewer)
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'none',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            'active',
            $lease->fresh()->status
        );
    }

    public function test_generic_update_cannot_initiate_termination_notice(): void
    {
        $lease = $this->lease();

        $payload = [
            'unit_id' => $lease->unit_id,
            'tenant_id' => $lease->tenant_id,
            'agent_id' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'notice',
            'termination_notice_date' => '2026-08-20',
            'rent_amount' => 3000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'proration_amount' => null,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'advance_received' => false,
            'advance_received_date' => null,
            'advance_received_method' => null,
            'advance_received_reference' => null,
            'advance_received_collector' => null,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'next_rent_increment_date' => null,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
            'notes' => null,
        ];

        $this
            ->actingAs($this->propertyManager())
            ->putJson(
                "/api/leases/{$lease->id}",
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $this->assertSame(
            'active',
            $lease->fresh()->status
        );
    }


    public function test_initiating_termination_cancels_untouched_future_rent_invoice(): void
    {
        $lease = $this->lease();

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-INIT-FUTURE',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-01',
            'status' => 'issued',
            'total_amount' => 3100,
            'vat_rate' => 0,
            'net_amount' => 3100,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        $this
            ->actingAs($this->propertyManager())
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'full',
                ]
            )
            ->assertOk();

        $this->assertSame(
            'cancelled',
            $invoice->fresh()->status
        );
    }

    public function test_initiating_termination_preserves_financially_touched_future_invoice(): void
    {
        $lease = $this->lease();

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-INIT-TOUCHED',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-01',
            'status' => 'issued',
            'total_amount' => 3100,
            'vat_rate' => 0,
            'net_amount' => 3100,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'payment_date' => '2026-08-10',
            'amount' => 1000,
            'payment_method' => 'cash',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        $this
            ->actingAs($this->propertyManager())
            ->postJson(
                "/api/leases/{$lease->id}/termination",
                [
                    'notice_date' => '2026-08-20',
                    'termination_date' => '2026-09-30',
                    'final_rent_mode' => 'prorate',
                ]
            )
            ->assertOk();

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertSame(
            1000,
            $invoice->fresh()->paymentPaidAmount()
        );
    }


    private function propertyManager(): User
    {
        return User::factory()->create([
            'role' => UserRole::PropertyManager,
        ]);
    }

    private function lease(
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' => 'Termination Initiation Building',
            'address' => 'Accra',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Termination Initiation Unit',
        ]);

        $tenant = Party::create([
            'name' => 'Termination Initiation Tenant',
            'type' => 'person',
        ]);

        $tenant->roles()->create([
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
