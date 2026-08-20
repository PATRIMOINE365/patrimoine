<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaseTerminationSettlementApiTest extends TestCase
{
    use RefreshDatabase;

    private function lease(
        string $status = 'notice'
    ): Lease {
        $building = Building::create([
            'name' => 'Settlement API Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Settlement API Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Settlement API Tenant',
            'phone' => '0209000099',
            'email' => uniqid(
                'phase9d2-',
                true
            ).'@example.test',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => $status,

            'termination_notice_date' =>
                $status === 'notice'
                    ? '2026-08-01'
                    : null,

            'termination_date' =>
                $status === 'notice'
                    ? '2026-08-31'
                    : null,

            'termination_final_rent_mode' =>
                $status === 'notice'
                    ? 'full'
                    : null,

            'termination_previous_status' =>
                $status === 'notice'
                    ? 'active'
                    : null,
        ]);
    }

    private function user(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function addFinancialPosition(
        Lease $lease
    ): void {
        Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-9D2-'.$lease->id,
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'advance_funding',
            'amount' => 3000,
            'transaction_date' => '2026-08-01',
        ]);
    }

    public function test_settlement_summary_requires_authentication(): void
    {
        $lease = $this->lease();

        $this
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertUnauthorized();
    }

    public function test_administrator_can_read_termination_settlement(): void
    {
        $lease = $this->lease();

        $this->addFinancialPosition(
            $lease
        );

        $this
            ->actingAs(
                $this->user(
                    'administrator'
                )
            )
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertOk()
            ->assertJsonPath(
                'lease.id',
                $lease->id
            )
            ->assertJsonPath(
                'lease.status',
                'notice'
            )
            ->assertJsonPath(
                'debt.total_outstanding',
                5000
            )
            ->assertJsonPath(
                'funds.consumable_advance_remaining',
                3000
            )
            ->assertJsonPath(
                'settlement.amount_still_owed_by_tenant',
                5000
            )
            ->assertJsonPath(
                'settlement.can_complete',
                false
            );
    }

    public function test_property_manager_can_read_termination_settlement(): void
    {
        $lease = $this->lease();

        $this
            ->actingAs(
                $this->user(
                    'property_manager'
                )
            )
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertOk()
            ->assertJsonPath(
                'lease.id',
                $lease->id
            );
    }

    public function test_viewer_can_read_termination_settlement_but_endpoint_is_read_only(): void
    {
        $lease = $this->lease();

        $viewer = $this->user(
            'viewer'
        );

        $before = $lease
            ->fresh()
            ->toArray();

        $this
            ->actingAs(
                $viewer
            )
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertOk()
            ->assertJsonPath(
                'lease.id',
                $lease->id
            );

        $after = $lease
            ->fresh()
            ->toArray();

        $this->assertSame(
            $before,
            $after
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_active_lease_returns_controlled_validation_response(): void
    {
        $lease = $this->lease(
            'active'
        );

        $this
            ->actingAs(
                $this->user(
                    'administrator'
                )
            )
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Lease termination settlement is available only for a Lease under termination notice or already Terminated.'
            );
    }

    public function test_missing_lease_returns_not_found(): void
    {
        $this
            ->actingAs(
                $this->user(
                    'administrator'
                )
            )
            ->getJson(
                '/api/leases/999999/termination-settlement'
            )
            ->assertNotFound();
    }

    public function test_reading_settlement_does_not_create_financial_records(): void
    {
        $lease = $this->lease();

        $this->addFinancialPosition(
            $lease
        );

        $counts = [
            'tenant_fund_transactions' =>
                DB::table(
                    'tenant_fund_transactions'
                )->count(),

            'security_deposit_applications' =>
                DB::table(
                    'security_deposit_applications'
                )->count(),

            'security_deposit_settlements' =>
                DB::table(
                    'security_deposit_settlements'
                )->count(),

            'journal_entries' =>
                DB::table(
                    'journal_entries'
                )->count(),
        ];

        $this
            ->actingAs(
                $this->user(
                    'administrator'
                )
            )
            ->getJson(
                "/api/leases/{$lease->id}/termination-settlement"
            )
            ->assertOk();

        foreach (
            $counts
            as $table => $expected
        ) {
            $this->assertSame(
                $expected,
                DB::table(
                    $table
                )->count(),
                "Reading termination settlement mutated {$table}."
            );
        }
    }
}
