<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Preserve business-record deletion integrity rules while exercising the
 * V1.0.5 controlled destructive Lease deletion workflow.
 */
class BusinessRecordDeletionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator =
            User::factory()->create([
                'role' =>
                    UserRole::Administrator,

                'password' =>
                    Hash::make(
                        'Password123!'
                    ),
            ]);

        Sanctum::actingAs(
            $this->administrator
        );
    }

    public function test_unreferenced_party_can_be_deleted(): void
    {
        $party = $this->party('Standalone Party');

        $this
            ->deleteJson(
                "/api/parties/{$party->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'parties',
            [
                'id' => $party->id,
            ]
        );
    }

    public function test_party_used_as_agent_is_retained_for_history(): void
    {
        $tenant = $this->party('Tenant');
        $agent = $this->party('Historical Agent');

        $unit = $this->unit();

        $lease = $this->lease(
            $unit,
            $tenant,
            status: 'draft',
            agent: $agent
        );

        $this
            ->deleteJson(
                "/api/parties/{$agent->id}"
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'This Party cannot be deleted because it is referenced by Lease, ownership, agency or financial history. Keep the Party so historical records remain understandable.'
            );

        $this->assertDatabaseHas(
            'parties',
            [
                'id' => $agent->id,
            ]
        );

        $this->assertDatabaseHas(
            'leases',
            [
                'id' => $lease->id,
                'agent_id' => $agent->id,
            ]
        );
    }

    public function test_building_with_units_cannot_be_deleted_by_cascade(): void
    {
        $building = Building::create([
            'name' => 'Protected Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $this
            ->deleteJson(
                "/api/buildings/{$building->id}"
            )
            ->assertStatus(409);

        $this->assertDatabaseHas(
            'buildings',
            [
                'id' => $building->id,
            ]
        );

        $this->assertDatabaseHas(
            'units',
            [
                'id' => $unit->id,
            ]
        );
    }

    public function test_empty_unreferenced_building_can_be_deleted(): void
    {
        $building = Building::create([
            'name' => 'Unused Building',
        ]);

        $this
            ->deleteJson(
                "/api/buildings/{$building->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'buildings',
            [
                'id' => $building->id,
            ]
        );
    }

    public function test_unit_with_lease_history_cannot_be_deleted(): void
    {
        $tenant = $this->party('Lease Tenant');

        $unit = $this->unit();

        $this->lease(
            $unit,
            $tenant,
            status: 'draft'
        );

        $this
            ->deleteJson(
                "/api/units/{$unit->id}"
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'This Unit cannot be deleted because Lease or financial history references it. Keep the Unit and terminate the Lease instead where applicable.'
            );

        $this->assertDatabaseHas(
            'units',
            [
                'id' => $unit->id,
            ]
        );
    }

    public function test_unreferenced_unit_can_be_deleted(): void
    {
        $unit = $this->unit();

        $this
            ->deleteJson(
                "/api/units/{$unit->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'units',
            [
                'id' => $unit->id,
            ]
        );
    }

    public function test_active_lease_can_be_destructively_deleted_when_safe(): void
    {
        $tenant =
            $this->party(
                'Active Tenant'
            );

        $lease =
            $this->lease(
                $this->unit(),
                $tenant,
                status: 'active'
            );

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                $this->leaseDeletionPayload(
                    'Active Lease was created in error.'
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'leases',
            [
                'id' => $lease->id,
            ]
        );
    }

    public function test_terminated_lease_can_be_destructively_deleted_when_safe(): void
    {
        $tenant =
            $this->party(
                'Former Tenant'
            );

        $lease =
            $this->lease(
                $this->unit(),
                $tenant,
                status: 'terminated'
            );

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                $this->leaseDeletionPayload(
                    'Terminated Lease was entered in error.'
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'leases',
            [
                'id' => $lease->id,
            ]
        );
    }

    public function test_unused_draft_lease_can_be_deleted_with_strong_confirmation(): void
    {
        $tenant =
            $this->party(
                'Draft Tenant'
            );

        $lease =
            $this->lease(
                $this->unit(),
                $tenant,
                status: 'draft'
            );

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                $this->leaseDeletionPayload(
                    'Draft Lease was created in error.'
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'leases',
            [
                'id' => $lease->id,
            ]
        );
    }

    public function test_attributable_financial_history_is_removed_by_controlled_lease_deletion(): void
    {
        $tenant =
            $this->party(
                'Financial Tenant'
            );

        $lease =
            $this->lease(
                $this->unit(),
                $tenant,
                status: 'draft'
            );

        $invoice =
            Invoice::create([
                'lease_id' =>
                    $lease->id,

                'invoice_number' =>
                    'DELETE-I-0001',

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
                    'draft',

                'total_amount' =>
                    1000,

                'vat_rate' =>
                    18,

                'net_amount' =>
                    847,

                'vat_amount' =>
                    153,
            ]);

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                $this->leaseDeletionPayload(
                    'Lease and attributable invoice were created in error.'
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'leases',
            [
                'id' => $lease->id,
            ]
        );

        $this->assertDatabaseMissing(
            'invoices',
            [
                'id' => $invoice->id,
            ]
        );
    }

    public function test_lease_delete_confirmation_validation_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' =>
                'fr',

            'currency' =>
                'GHS',
        ]);

        app()->setLocale('fr');

        $tenant =
            $this->party(
                'Locataire'
            );

        $lease =
            $this->lease(
                $this->unit(),
                $tenant,
                status: 'active'
            );

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                []
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.reason.0',
                'Le champ motif est obligatoire.'
            );

        $this->assertDatabaseHas(
            'leases',
            [
                'id' =>
                    $lease->id,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    private function leaseDeletionPayload(
        string $reason
    ): array {
        return [
            'reason' =>
                $reason,

            'confirmation' =>
                'DELETE',

            'current_password' =>
                'Password123!',
        ];
    }

    private function party(string $name): Party
    {
        return Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => '0200000000',
            'email' => strtolower(
                str_replace(
                    ' ',
                    '.',
                    $name
                )
            )
                .'@delete-integrity.test',
        ]);
    }

    private function unit(): Unit
    {
        $building = Building::create([
            'name' => 'Building '.uniqid(),
        ]);

        return Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit '.uniqid(),
        ]);
    }

    private function lease(
        Unit $unit,
        Party $tenant,
        string $status,
        ?Party $agent = null
    ): Lease {
        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'agent_id' => $agent?->id,
            'start_date' => '2026-08-01',
            'status' => $status,
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 18,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);
    }
}
