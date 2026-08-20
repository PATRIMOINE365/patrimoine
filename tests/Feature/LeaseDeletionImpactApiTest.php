<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaseDeletionImpactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_preview_lease_deletion_impact(): void
    {
        Sanctum::actingAs(
            $this->user(
                UserRole::Administrator
            )
        );

        $lease =
            $this->lease();

        $this
            ->getJson(
                "/api/leases/{$lease->id}/deletion-impact"
            )
            ->assertOk()
            ->assertJsonPath(
                'lease.id',
                $lease->id
            )
            ->assertJsonStructure([
                'lease',
                'eligibility' => [
                    'safe_to_execute',
                    'blocking_reasons',
                ],
                'accounting' => [
                    'runtime_enabled',
                    'reversal_candidates',
                    'already_neutralized',
                    'opening_balance_actions',
                    'shared_entries',
                    'unclassified_entries',
                ],
                'operational' => [
                    'delete_in_order',
                    'monetary_restoration',
                ],
                'preserve',
                'documents',
                'execution_contract',
            ]);
    }

    public function test_property_manager_can_preview_lease_deletion_impact(): void
    {
        Sanctum::actingAs(
            $this->user(
                UserRole::PropertyManager
            )
        );

        $lease =
            $this->lease();

        $this
            ->getJson(
                "/api/leases/{$lease->id}/deletion-impact"
            )
            ->assertOk();
    }

    public function test_viewer_cannot_preview_lease_deletion_impact(): void
    {
        Sanctum::actingAs(
            $this->user(
                UserRole::Viewer
            )
        );

        $lease =
            $this->lease();

        $this
            ->getJson(
                "/api/leases/{$lease->id}/deletion-impact"
            )
            ->assertForbidden();
    }

    public function test_property_manager_reaches_destructive_delete_validation(): void
    {
        Sanctum::actingAs(
            $this->user(
                UserRole::PropertyManager
            )
        );

        $lease =
            $this->lease();

        /*
         * 422 proves the Property Manager passed authorization and reached
         * the mandatory strong-confirmation contract.
         */
        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                []
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
                'confirmation',
                'current_password',
            ]);
    }

    public function test_viewer_cannot_attempt_destructive_lease_delete(): void
    {
        Sanctum::actingAs(
            $this->user(
                UserRole::Viewer
            )
        );

        $lease =
            $this->lease();

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                []
            )
            ->assertForbidden();
    }

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' =>
                $role,

            'is_active' =>
                true,

            'email_verified_at' =>
                now(),
        ]);
    }

    private function lease(): Lease
    {
        $tenant =
            Party::query()->create([
                'type' =>
                    'person',

                'name' =>
                    '10D4 Impact Tenant',
            ]);

        $building =
            Building::query()->create([
                'name' =>
                    '10D4 Impact Building',
            ]);

        $unit =
            Unit::query()->create([
                'building_id' =>
                    $building->id,

                'name' =>
                    '10D4 Impact Unit',
            ]);

        return Lease::query()->create([
            'unit_id' =>
                $unit->id,

            'tenant_id' =>
                $tenant->id,

            'start_date' =>
                '2026-01-01',

            'end_date' =>
                '2026-12-31',

            'status' =>
                'draft',

            'rent_amount' =>
                1000,

            'payment_frequency' =>
                'monthly',

            'due_day' =>
                1,

            'vat_rate' =>
                0,

            'proration_amount' =>
                0,

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
        ]);
    }
}
