<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies the Patrimoine Rent Reserve transactional API.
 */
class RentReserveApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a funded Rent Reserve with an outstanding Invoice.
     *
     * @return array{
     *     lease: Lease,
     *     invoice: Invoice,
     *     account: TenantFundAccount,
     *     owner: Party
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Rent Reserve API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Reserve API Owner',
            'phone' => '0200000700',
            'email' => 'reserve-api-owner@example.test',
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
            'name' => 'Reserve API Tenant',
            'phone' => '0200000701',
            'email' => 'reserve-api-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'notice',
            'termination_notice_date' => '2026-07-15',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-RESERVE-API-001',
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
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 15000,
            'transaction_date' => '2026-01-01',
        ]);

        return compact(
            'lease',
            'invoice',
            'account',
            'owner'
        );
    }

    /**
     * Rent Reserve consumption settles rent and creates owner entitlement.
     */
    public function test_rent_reserve_can_settle_invoice(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-rent",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-01',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'rent_consumption'
            )
            ->assertJsonPath(
                'rent_reserve.balance',
                10000
            )
            ->assertJsonPath(
                'invoice.status',
                'paid'
            )
            ->assertJsonPath(
                'invoice.outstanding_amount',
                0
            );

        $ownerAccount = OwnerAccount::query()
            ->where('party_id', $context['owner']->id)
            ->firstOrFail();

        $this->assertSame(
            5000,
            $ownerAccount->creditedAmount()
        );
    }

    /**
     * Rent Reserve cannot be consumed before termination notice.
     */
    public function test_rent_reserve_rejects_lease_before_notice(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'active',
            'termination_notice_date' => null,
        ]);

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-rent",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rent_reserve',
            ]);
    }

    /**
     * Consumption cannot exceed the available Rent Reserve balance.
     */
    public function test_rent_reserve_cannot_be_overdrawn(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-rent",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 20000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rent_reserve',
            ]);
    }

    /**
     * A non-Rent-Reserve account cannot use the Rent Reserve endpoint.
     */
    public function test_non_reserve_account_is_rejected(): void
    {
        $context = $this->createContext();

        $advance = TenantFundAccount::create([
            'lease_id' => $context['lease']->id,
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        $this->postJson(
            "/api/tenant-funds/{$advance->id}/consume-rent",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 1000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tenant_fund_account',
            ]);
    }
}
