<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies automated scheduled Invoice generation.
 */
class GenerateDueInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an active Lease suitable for command testing.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createLease(
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' => 'Scheduled Billing Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Scheduled Billing Tenant',
            'phone' => '0200001200',
            'email' => 'scheduled-billing@example.test',
        ]);

        return Lease::create(
            array_merge([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-06-01',
                'rent_amount' => 5000,
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 18,
                'proration_amount' => null,
                'security_deposit_amount' => 0,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
                'status' => 'active',
            ], $overrides)
        );
    }

    /**
     * Command generates all missing billing periods through the cutoff date.
     */
    public function test_command_generates_due_invoices(): void
    {
        $lease = $this->createLease();

        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-08-11',
            ]
        )
            ->expectsOutput(
                'Lease #'.$lease->id.': generated 3 invoice(s).'
            )
            ->assertExitCode(0);

        $this->assertDatabaseCount(
            'invoices',
            3
        );

        $this->assertSame(
            [
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
            ],
            Invoice::query()
                ->orderBy('period_start')
                ->get()
                ->map(
                    fn (Invoice $invoice): string => $invoice->period_start->toDateString()
                )
                ->all()
        );

        /*
         * Background Activity Log exclusion: test_command_generates_due_invoices
         *
         * Automatic invoice generation must not be represented as a human Activity Log event.
         * Activity Log records meaningful human actions only.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    /**
     * Re-running the command does not duplicate existing Invoice periods.
     */
    public function test_command_is_idempotent(): void
    {
        $this->createLease();

        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-08-11',
            ]
        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'invoices',
            3
        );

        /*
         * Running the exact same billing cycle again must create nothing
         * additional.
         */
        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-08-11',
            ]
        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'invoices',
            3
        );
    }

    /**
     * Draft and terminated Leases are excluded from scheduled billing.
     */
    public function test_command_skips_ineligible_leases(): void
    {
        $this->createLease([
            'status' => 'draft',
        ]);

        $this->createLease([
            'status' => 'terminated',
        ]);

        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-08-11',
            ]
        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'invoices',
            0
        );
    }

    /**
     * Notice-stage Leases continue to generate rent billing.
     */
    public function test_command_bills_notice_leases(): void
    {
        $this->createLease([
            'status' => 'notice',
            'termination_notice_date' => '2026-07-15',
        ]);

        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-08-11',
            ]
        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'invoices',
            3
        );
    }

    /**
     * Invalid cutoff date is rejected before any billing work occurs.
     */
    public function test_command_rejects_invalid_through_date(): void
    {
        $this->createLease();

        $this->artisan(
            'patrimoine:generate-due-invoices',
            [
                '--through' => '2026-99-99',
            ]
        )
            ->expectsOutput(
                'The --through option must be a valid date in YYYY-MM-DD format.'
            )
            ->assertExitCode(1);

        $this->assertDatabaseCount(
            'invoices',
            0
        );
    }
}
