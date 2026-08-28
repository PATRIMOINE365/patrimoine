<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Platform staff supporting a customer from the console: reading the
 * customer's own records, and correcting a Lease on their behalf.
 */
class AdminCustomerSupportTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $platformOrganisation;

    private User $platformAdmin;

    private Organisation $customer;

    private Lease $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformOrganisation = Organisation::factory()->create([
            'name' => 'Patrimoine 365',
            'is_platform' => true,
        ]);

        $this->platformAdmin = OrganisationContext::runAs(
            (int) $this->platformOrganisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->platformOrganisation)
                ->create([
                    'email' => 'staff@patrimoine365.com',
                    'role' => 'administrator',
                ])
        );

        $this->customer = Organisation::factory()->create([
            'name' => 'Customer Ltd',
        ]);

        $this->lease = OrganisationContext::runAs(
            (int) $this->customer->id,
            function (): Lease {
                $building = Building::create(['name' => 'Customer Building']);

                $unit = Unit::create([
                    'building_id' => $building->id,
                    'name' => 'Unit A',
                ]);

                $tenant = Party::create([
                    'type' => 'person',
                    'name' => 'Customer Tenant',
                ]);

                return Lease::create([
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                    'status' => 'active',
                    'rent_amount' => 10000,
                    'payment_frequency' => 'monthly',
                    'due_day' => 1,
                    'vat_rate' => 0,
                    'proration_amount' => 0,
                    'security_deposit_amount' => 0,
                    'advance_payment_amount' => 0,
                    'rent_reserve_amount' => 0,
                    'management_fee_type' => 'none',
                    'management_fee_value' => 0,
                    'agent_commission_amount' => 0,
                ]);
            }
        );
    }

    private function actAsPlatformAdmin(): void
    {
        Sanctum::actingAs($this->platformAdmin);
    }

    public function test_staff_can_read_a_customers_records(): void
    {
        $this->actAsPlatformAdmin();

        $response = $this->getJson(
            "/api/admin/organisations/{$this->customer->id}/records?dataset=leases"
        );

        $response->assertOk();
        $response->assertJsonPath('organisation.id', (int) $this->customer->id);
        $response->assertJsonPath('counts.leases', 1);
        $response->assertJsonPath('data.0.id', $this->lease->id);
        $response->assertJsonPath('data.0.tenant_name', 'Customer Tenant');
    }

    /**
     * The drill-down must never leak a second organisation's rows: it
     * runs inside OrganisationContext rather than lifting the scopes.
     */
    public function test_records_are_confined_to_the_requested_organisation(): void
    {
        $other = Organisation::factory()->create(['name' => 'Someone Else Ltd']);

        OrganisationContext::runAs(
            (int) $other->id,
            fn (): Party => Party::create([
                'type' => 'person',
                'name' => 'Party Of Another Org',
            ])
        );

        $this->actAsPlatformAdmin();

        $response = $this->getJson(
            "/api/admin/organisations/{$this->customer->id}/records?dataset=parties"
        );

        $response->assertOk();

        $names = array_column($response->json('data'), 'name');

        $this->assertContains('Customer Tenant', $names);
        $this->assertNotContains('Party Of Another Org', $names);
    }

    public function test_customer_users_cannot_reach_customer_records(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'administrator'])
        );

        $this->getJson(
            "/api/admin/organisations/{$this->customer->id}/records"
        )->assertForbidden();
    }

    public function test_safe_lease_fields_save_without_a_reason(): void
    {
        $this->actAsPlatformAdmin();

        $response = $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            ['notes' => 'Corrected by support.']
        );

        $response->assertOk();
        $response->assertJsonPath('lease.notes', 'Corrected by support.');

        $this->assertSame(
            'Corrected by support.',
            OrganisationContext::runAs(
                (int) $this->customer->id,
                fn () => Lease::find($this->lease->id)->notes
            )
        );
    }

    /**
     * Rent is what invoices and journal entries were derived from, so it
     * may not be rewritten without saying why.
     */
    public function test_posted_impact_fields_require_a_reason(): void
    {
        $this->actAsPlatformAdmin();

        $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            ['rent_amount' => 12000]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->assertSame(
            10000,
            (int) OrganisationContext::runAs(
                (int) $this->customer->id,
                fn () => Lease::find($this->lease->id)->rent_amount
            )
        );
    }

    public function test_posted_impact_fields_save_with_a_reason_and_are_audited(): void
    {
        $this->actAsPlatformAdmin();

        $response = $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            [
                'rent_amount' => 12000,
                'reason' => 'Customer typed 10,000 instead of 12,000 at signup.',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('lease.rent_amount', 12000);

        $log = ActivityLog::withoutGlobalScopes()
            ->where('action', 'platform.lease.corrected')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $this->assertSame(
            (int) $this->customer->id,
            (int) $log->organisation_id
        );

        $this->assertContains(
            'rent_amount',
            $log->metadata['posted_impact_fields'] ?? []
        );

        $this->assertSame(
            'Customer typed 10,000 instead of 12,000 at signup.',
            $log->metadata['reason'] ?? null
        );

        $this->assertTrue(
            (bool) ($log->metadata['performed_by_platform_staff'] ?? false)
        );
    }

    /**
     * The correction changes the contract only. Invoices already issued
     * keep the figures they were issued with, which is exactly why the
     * reason is demanded.
     */
    public function test_correcting_a_lease_does_not_rewrite_posted_invoices(): void
    {
        $invoice = OrganisationContext::runAs(
            (int) $this->customer->id,
            fn (): Invoice => Invoice::create([
                'lease_id' => $this->lease->id,
                'invoice_number' => 'INV-SUPPORT-1',
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-05',
                'status' => 'issued',
                'total_amount' => 10000,
                'vat_rate' => 0,
                'net_amount' => 10000,
                'vat_amount' => 0,
            ])
        );

        $this->actAsPlatformAdmin();

        $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            [
                'rent_amount' => 12000,
                'reason' => 'Wrong rent captured at signup.',
            ]
        )->assertOk();

        $this->assertSame(
            10000,
            (int) OrganisationContext::runAs(
                (int) $this->customer->id,
                fn () => Invoice::find($invoice->id)->total_amount
            )
        );
    }

    public function test_lease_show_reports_the_posted_footprint(): void
    {
        OrganisationContext::runAs(
            (int) $this->customer->id,
            fn (): Invoice => Invoice::create([
                'lease_id' => $this->lease->id,
                'invoice_number' => 'INV-SUPPORT-2',
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
                'issue_date' => '2026-02-01',
                'due_date' => '2026-02-05',
                'status' => 'issued',
                'total_amount' => 10000,
                'vat_rate' => 0,
                'net_amount' => 10000,
                'vat_amount' => 0,
            ])
        );

        $this->actAsPlatformAdmin();

        $response = $this->getJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}"
        );

        $response->assertOk();
        $response->assertJsonPath('posted.invoices', 1);
        $response->assertJsonPath('posted.has_posted_records', true);
        $response->assertJsonPath('posted.invoiced_total', 10000);

        $this->assertContains(
            'rent_amount',
            $response->json('fields.posted_impact')
        );

        $this->assertContains(
            'notes',
            $response->json('fields.safe')
        );
    }

    /**
     * An empty or unparseable body must not read as a successful save.
     * Every field is nullable, so without an explicit guard the request
     * would validate cleanly and answer 200 having changed nothing.
     */
    public function test_a_request_with_no_lease_fields_is_refused(): void
    {
        $this->actAsPlatformAdmin();

        $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            ['reason' => 'Only a reason, no fields.']
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lease']);
    }

    /**
     * Submitting the values a lease already holds is a genuine no-op and
     * stays a success, distinct from submitting nothing at all.
     */
    public function test_submitting_unchanged_values_is_a_no_op(): void
    {
        $this->actAsPlatformAdmin();

        $this->patchJson(
            "/api/admin/organisations/{$this->customer->id}/leases/{$this->lease->id}",
            ['rent_amount' => 10000]
        )
            ->assertOk()
            ->assertJsonPath('changed', []);
    }

    public function test_emails_endpoint_degrades_without_a_provider_key(): void
    {
        config(['services.resend.key' => null]);
        putenv('RESEND_API_KEY');

        $this->actAsPlatformAdmin();

        $response = $this->getJson('/api/admin/emails?box=received');

        $response->assertOk();
        $response->assertJsonPath('configured', false);
        $response->assertJsonPath('data', []);
    }

    public function test_customer_users_cannot_reach_the_mailbox(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'administrator'])
        );

        $this->getJson('/api/admin/emails')->assertForbidden();
    }
}
