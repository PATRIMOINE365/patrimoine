<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the Patrimoine read-only dashboard API.
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a minimal occupied property context.
     *
     * @return array{
     *     building: Building,
     *     unit: Unit,
     *     tenant: Party,
     *     owner: Party,
     *     lease: Lease
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Dashboard API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Dashboard API Owner',
            'phone' => '0200001000',
            'email' => 'dashboard-api-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Dashboard API Tenant',
            'phone' => '0200001001',
            'email' => 'dashboard-api-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        return compact(
            'building',
            'unit',
            'tenant',
            'owner',
            'lease'
        );
    }

    /**
     * Dashboard summary exposes the primary V1 metrics.
     */
    public function test_dashboard_summary_returns_metrics(): void
    {
        $context = $this->createContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-API-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 3000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        $ownerAccount = OwnerAccount::create([
            'party_id' => $context['owner']->id,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $ownerAccount->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 3000,
            'transaction_date' => '2026-08-05',
        ]);

        $response = $this->getJson(
            '/api/dashboard?as_of=2026-08-11'
        );

        $response
            ->assertOk()
            ->assertJsonPath('as_of', '2026-08-11')
            ->assertJsonPath(
                'metrics.total_buildings',
                1
            )
            ->assertJsonPath(
                'metrics.total_units',
                1
            )
            ->assertJsonPath(
                'metrics.occupied_units',
                1
            )
            ->assertJsonPath(
                'metrics.vacant_units',
                0
            )
            ->assertJsonPath(
                'metrics.rent_due',
                5000
            )
            ->assertJsonPath(
                'metrics.rent_overdue',
                5000
            )
            ->assertJsonPath(
                'metrics.rent_collected_this_month',
                3000
            )
            ->assertJsonPath(
                'metrics.owner_funds_held',
                3000
            );
    }

    /**
     * Dashboard as-of date must use YYYY-MM-DD format.
     */
    public function test_dashboard_rejects_invalid_as_of_date(): void
    {
        $this->getJson(
            '/api/dashboard?as_of=11-08-2026'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'as_of',
            ]);
    }

    /**
     * Overdue endpoint returns tenant, Unit and Building context.
     */
    public function test_dashboard_returns_overdue_obligations(): void
    {
        $context = $this->createContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-OVERDUE-API-001',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $response = $this->getJson(
            '/api/dashboard/overdue?as_of=2026-08-11'
        );

        $response
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath(
                'data.0.invoice_number',
                'INV-OVERDUE-API-001'
            )
            ->assertJsonPath(
                'data.0.outstanding_amount',
                5000
            )
            ->assertJsonPath(
                'data.0.tenant.name',
                'Dashboard API Tenant'
            )
            ->assertJsonPath(
                'data.0.unit.name',
                'Unit 1'
            )
            ->assertJsonPath(
                'data.0.building.name',
                'Dashboard API Building'
            );
    }

    /**
     * Invoice due exactly on the as-of date is not overdue yet.
     */
    public function test_invoice_due_today_is_not_in_overdue_list(): void
    {
        $context = $this->createContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DUE-TODAY-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-11',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $this->getJson(
            '/api/dashboard/overdue?as_of=2026-08-11'
        )
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    /**
     * Upcoming endpoint returns obligations within the requested window.
     */
    public function test_dashboard_returns_upcoming_obligations(): void
    {
        $context = $this->createContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-UPCOMING-API-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-OUTSIDE-WINDOW-001',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-25',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $response = $this->getJson(
            '/api/dashboard/upcoming?as_of=2026-08-11&days=7'
        );

        $response
            ->assertOk()
            ->assertJsonPath('days', 7)
            ->assertJsonPath('count', 1)
            ->assertJsonPath(
                'data.0.invoice_number',
                'INV-UPCOMING-API-001'
            );
    }

    /**
     * Upcoming look-ahead window is bounded to avoid unreasonable queries.
     */
    public function test_dashboard_rejects_invalid_upcoming_window(): void
    {
        $this->getJson(
            '/api/dashboard/upcoming?as_of=2026-08-11&days=365'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'days',
            ]);
    }
}
