<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Integration coverage for the Patrimoine V1.0.1 Tenant workspace.
 *
 * The Tenant page is intentionally read-oriented. It composes existing
 * Party, Lease, Report, Payment and Tenant Fund APIs rather than creating
 * a duplicate operational tenant domain.
 */
class TenantManagementTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a Party carrying the Tenant role.
     */
    private function createTenant(
        string $name,
        string $phone,
        string $email
    ): Party {
        $tenant = Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        return $tenant;
    }

    /**
     * Create a minimum property and Lease for one Tenant.
     */
    private function createLease(
        Party $tenant,
        int $rentAmount = 5000
    ): Lease {
        $building = Building::create([
            'name' => 'Tenant Management Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Tenant Management Owner',
            'phone' => '0200009900',
            'email' => 'tenant-management-owner@example.test',
        ]);

        $owner->roles()->create([
            'role' => 'owner',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Tenant Management Unit',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => $rentAmount,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * The browser Tenant workspace route is available and contains the
     * expected directory/detail integration points.
     */
    public function test_tenant_workspace_page_is_available(): void
    {
        $this->get('/tenants')
            ->assertOk()
            ->assertSee(
                'tenant-workspace',
                false
            )
            ->assertSee(
                'tenant-search',
                false
            )
            ->assertSee(
                'tenant-list',
                false
            )
            ->assertSee(
                'tenant-detail',
                false
            );
    }

    /**
     * Tenant directory queries never return Parties that do not carry the
     * Tenant role, even when their searchable details match.
     */
    public function test_tenant_directory_combines_role_and_search_filters(): void
    {
        $tenant = $this->createTenant(
            'Needle Tenant',
            '0200009901',
            'needle-tenant@example.test'
        );

        $ownerOnly = Party::create([
            'type' => 'person',
            'name' => 'Needle Owner',
            'phone' => '0200009902',
            'email' => 'needle-owner@example.test',
        ]);

        $ownerOnly->roles()->create([
            'role' => 'owner',
        ]);

        $response = $this->getJson(
            '/api/parties?role=tenant&search=Needle&per_page=25'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $tenant->id
            )
            ->assertJsonMissing([
                'id' => $ownerOnly->id,
            ]);
    }

    /**
     * Tenant directory pagination remains stable beyond the first 50 results,
     * matching the page size used by resources/js/tenants.js.
     */
    public function test_tenant_directory_paginates_at_workspace_page_size(): void
    {
        for ($index = 1; $index <= 51; $index++) {
            $this->createTenant(
                sprintf(
                    'Pagination Tenant %02d',
                    $index
                ),
                sprintf(
                    '020001%04d',
                    $index
                ),
                sprintf(
                    'pagination-tenant-%02d@example.test',
                    $index
                )
            );
        }

        $firstPage = $this->getJson(
            '/api/parties?role=tenant&per_page=50&page=1'
        );

        $firstPage
            ->assertOk()
            ->assertJsonPath(
                'total',
                51
            )
            ->assertJsonPath(
                'per_page',
                50
            )
            ->assertJsonPath(
                'current_page',
                1
            )
            ->assertJsonCount(
                50,
                'data'
            );

        $secondPage = $this->getJson(
            '/api/parties?role=tenant&per_page=50&page=2'
        );

        $secondPage
            ->assertOk()
            ->assertJsonPath(
                'current_page',
                2
            )
            ->assertJsonCount(
                1,
                'data'
            );
    }

    /**
     * A Tenant with no Lease or financial activity still produces a valid,
     * zero-valued Tenant Statement rather than an application error.
     */
    public function test_tenant_without_financial_history_has_valid_empty_statement(): void
    {
        $tenant = $this->createTenant(
            'Empty Tenant',
            '0200009910',
            'empty-tenant@example.test'
        );

        $this->getJson(
            "/api/leases?tenant_id={$tenant->id}&per_page=100"
        )
            ->assertOk()
            ->assertJsonPath(
                'total',
                0
            )
            ->assertJsonCount(
                0,
                'data'
            );

        $this->getJson(
            "/api/reports/tenants/{$tenant->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'summary.rent_outstanding',
                0
            )
            ->assertJsonPath(
                'summary.security_deposit_debt_outstanding',
                0
            )
            ->assertJsonPath(
                'summary.total_outstanding',
                0
            )
            ->assertJsonPath(
                'summary.rent_reserve_balance',
                0
            )
            ->assertJsonPath(
                'summary.consumable_advance_balance',
                0
            )
            ->assertJsonPath(
                'summary.security_deposit_balance',
                0
            )
            ->assertJsonCount(
                0,
                'invoices'
            )
            ->assertJsonCount(
                0,
                'payments'
            );
    }

    /**
     * The Tenant workspace financial sources preserve receivable type
     * separation and expose actual ledger-derived held-fund balances.
     */
    public function test_tenant_financial_integration_preserves_receivable_and_fund_types(): void
    {
        $tenant = $this->createTenant(
            'Financial Tenant',
            '0200009920',
            'financial-tenant@example.test'
        );

        $lease = $this->createLease(
            $tenant
        );

        $rentInvoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-TENANT-C4-RENT',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'partial',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-TENANT-C4-DEPOSIT-DEBT',
            'type' => 'security_deposit_debt',
            'period_start' => '2026-08-10',
            'period_end' => '2026-08-10',
            'issue_date' => '2026-08-10',
            'due_date' => '2026-08-10',
            'status' => 'issued',
            'total_amount' => 2000,
            'vat_rate' => 0,
            'net_amount' => 2000,
            'vat_amount' => 0,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 1000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'TENANT-C4-PAYMENT',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $rentInvoice->id,
            'amount' => 1000,
        ]);

        $reserve = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $reserve->id,
            'payment_id' => $payment->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 3000,
            'transaction_date' => '2026-08-05',
        ]);

        $advance = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $advance->id,
            'direction' => 'credit',
            'category' => 'advance_funding',
            'amount' => 2000,
            'transaction_date' => '2026-08-05',
        ]);

        $deposit = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $deposit->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => 1000,
            'transaction_date' => '2026-08-05',
        ]);

        /*
         * Tenant Statement is the authoritative financial projection used by
         * the Tenant workspace.
         */
        $this->getJson(
            "/api/reports/tenants/{$tenant->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'summary.rent_outstanding',
                4000
            )
            ->assertJsonPath(
                'summary.security_deposit_debt_outstanding',
                2000
            )
            ->assertJsonPath(
                'summary.total_outstanding',
                6000
            )
            ->assertJsonPath(
                'summary.rent_reserve_balance',
                3000
            )
            ->assertJsonPath(
                'summary.consumable_advance_balance',
                2000
            )
            ->assertJsonPath(
                'summary.security_deposit_balance',
                1000
            )
            ->assertJsonFragment([
                'invoice_number' => 'INV-TENANT-C4-RENT',
                'type' => 'rent',
            ])
            ->assertJsonFragment([
                'invoice_number' => 'INV-TENANT-C4-DEPOSIT-DEBT',
                'type' => 'security_deposit_debt',
            ]);

        /*
         * Operational APIs used by C3/C4 continue to expose persisted Payment
         * IDs and the auditable Tenant Fund transaction history.
         */
        $this->getJson(
            "/api/payments?lease_id={$lease->id}&per_page=100"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $payment->id
            );

        $this->getJson(
            "/api/leases/{$lease->id}"
        )
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'rent_reserve',
            ])
            ->assertJsonFragment([
                'category' => 'reserve_funding',
                'amount' => 3000,
            ])
            ->assertJsonFragment([
                'type' => 'consumable_advance',
            ])
            ->assertJsonFragment([
                'type' => 'security_deposit',
            ]);
    }
}
