<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
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
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Consumable Advance transactional API.
 */
class ConsumableAdvanceApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a funded Consumable Advance with an outstanding Invoice.
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
            'name' => 'Consumable Advance API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Advance API Owner',
            'phone' => '0200001400',
            'email' => 'advance-api-owner@example.test',
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
            'name' => 'Advance API Tenant',
            'phone' => '0200001401',
            'email' => 'advance-api-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-ADV-API-001',
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
     * Consumable Advance may settle rent during an active Lease.
     */
    public function test_advance_can_settle_invoice_through_api(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-advance",
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
                'advance_consumption'
            )
            ->assertJsonPath(
                'consumable_advance.balance',
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
     * Partial consumption leaves the Invoice partially outstanding.
     */
    public function test_advance_can_partially_settle_invoice_through_api(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-advance",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 2000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'invoice.status',
                'partial'
            )
            ->assertJsonPath(
                'invoice.paid_amount',
                2000
            )
            ->assertJsonPath(
                'invoice.outstanding_amount',
                3000
            )
            ->assertJsonPath(
                'consumable_advance.balance',
                13000
            );
    }

    /**
     * Consumption cannot exceed the available advance balance.
     */
    public function test_advance_cannot_be_overdrawn_through_api(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-advance",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 20000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'consumable_advance',
            ]);
    }

    /**
     * Consumption cannot exceed the Invoice's remaining obligation.
     */
    public function test_advance_cannot_exceed_invoice_balance_through_api(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-advance",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 6000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'consumable_advance',
            ]);
    }

    /**
     * A non-advance account cannot use this endpoint.
     */
    public function test_non_advance_account_is_rejected(): void
    {
        $context = $this->createContext();

        $reserve = TenantFundAccount::create([
            'lease_id' => $context['lease']->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        $this->postJson(
            "/api/tenant-funds/{$reserve->id}/consume-advance",
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

    /**
     * Draft Leases cannot consume advance funds.
     */
    public function test_draft_lease_cannot_consume_advance_through_api(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'draft',
        ]);

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/consume-advance",
            [
                'invoice_id' => $context['invoice']->id,
                'amount' => 1000,
                'transaction_date' => '2026-08-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'consumable_advance',
            ]);
    }

    /**
     * Consumable Advance service failures are returned in French.
     */
    public function test_consumable_advance_business_error_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createContext();

        $context['account']->update([
            'status' => 'closed',
        ]);

        $this
            ->postJson(
                "/api/tenant-funds/{$context['account']->id}/consume-advance",
                [
                    'invoice_id' => $context['invoice']->id,
                    'amount' => 100,
                    'transaction_date' => now()->toDateString(),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.consumable_advance.0',
                'Le compte d’avance consommable est fermé.'
            );
    }
}
