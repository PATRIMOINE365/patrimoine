<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Party;
use App\Models\SecurityDepositApplication;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SecurityDepositApplicationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    private Lease $lease;

    private Invoice $invoice;

    private TenantFundAccount $securityDeposit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator =
            User::factory()->create([
                'role' => 'administrator',

                'is_active' => true,
            ]);

        app(
            SystemChartOfAccounts::class
        )->install();

        $context =
            $this->financialContext();

        $this->lease =
            $context['lease'];

        $this->invoice =
            $context['invoice'];

        $this->securityDeposit =
            $context['security_deposit'];
    }

    public function test_administrator_can_apply_security_deposit_to_rent_receivable(): void
    {
        $beforeFund =
            $this->securityDeposit->balance();

        $beforeOutstanding =
            $this->invoice->outstandingAmount();

        $response =
            $this
                ->actingAs(
                    $this->administrator
                )
                ->postJson(
                    "/api/leases/{$this->lease->id}/security-deposit/apply",
                    [
                        'invoice_id' => $this->invoice->id,

                        'amount' => 2500,

                        'transaction_date' => '2026-08-19',

                        'notes' => 'Apply deposit to rent debt',
                    ]
                )
                ->assertCreated();

        $application =
            SecurityDepositApplication::query()
                ->sole();

        $this->assertSame(
            2500,
            $application->amount
        );

        $this->assertSame(
            $this->lease->id,
            $application->lease_id
        );

        $this->assertSame(
            $this->invoice->id,
            $application->invoice_id
        );

        $this->assertSame(
            $beforeFund - 2500,
            $this->securityDeposit
                ->fresh()
                ->balance()
        );

        $this->assertSame(
            $beforeOutstanding - 2500,
            $this->invoice
                ->fresh()
                ->outstandingAmount()
        );

        $transaction =
            TenantFundTransaction::query()
                ->findOrFail(
                    $application
                        ->tenant_fund_transaction_id
                );

        $this->assertSame(
            'debit',
            $transaction->direction
        );

        $this->assertSame(
            'deposit_deduction',
            $transaction->category
        );

        $this->assertSame(
            2500,
            $transaction->amount
        );

        $this->assertSame(
            $application->id,
            $response->json('id')
        );
    }

    public function test_application_cannot_exceed_available_security_deposit(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 6001,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            0
        );

        $this->assertSame(
            6000,
            $this->securityDeposit
                ->fresh()
                ->balance()
        );
    }

    public function test_application_cannot_exceed_invoice_outstanding_amount(): void
    {
        $this->invoice->update([
            'total_amount' => 3000,

            'net_amount' => 3000,

            'vat_amount' => 0,
        ]);

        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 3001,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            0
        );

        $this->assertSame(
            6000,
            $this->securityDeposit
                ->fresh()
                ->balance()
        );
    }

    public function test_security_deposit_application_is_counted_by_invoice_paid_amount(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 2000,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertCreated();

        $invoice =
            $this->invoice->fresh();

        $this->assertSame(
            2000,
            $invoice
                ->securityDepositAppliedAmount()
        );

        $this->assertSame(
            2000,
            $invoice->paidAmount()
        );

        $this->assertSame(
            8000,
            $invoice->outstandingAmount()
        );
    }

    public function test_property_manager_can_apply_security_deposit(): void
    {
        $manager =
            User::factory()->create([
                'role' => 'property_manager',

                'is_active' => true,
            ]);

        $this
            ->actingAs($manager)
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 1000,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseHas(
            'security_deposit_applications',
            [
                'lease_id' => $this->lease->id,

                'invoice_id' => $this->invoice->id,

                'amount' => 1000,
            ]
        );
    }

    public function test_viewer_cannot_apply_security_deposit(): void
    {
        $viewer =
            User::factory()->create([
                'role' => 'viewer',

                'is_active' => true,
            ]);

        $this
            ->actingAs($viewer)
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 1000,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            0
        );
    }

    public function test_invoice_from_another_lease_cannot_be_settled(): void
    {
        $other =
            $this->financialContext(
                suffix: 'other'
            );

        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $other['invoice']->id,

                    'amount' => 500,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            0
        );
    }

    public function test_application_creates_activity_log_event(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 1500,

                    'transaction_date' => '2026-08-19',

                    'notes' => 'Activity test',
                ]
            )
            ->assertCreated();

        $event =
            ActivityLog::query()
                ->sole();

        $this->assertSame(
            'security_deposit.applied',
            $event->action
        );

        $this->assertSame(
            'security_deposit_application',
            $event->entity_type
        );

        $this->assertSame(
            1500,
            $event->snapshot['amount']
        );
    }

    public function test_repeated_applications_respect_remaining_security_deposit(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 4000,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertCreated();

        $this->assertSame(
            6000,
            $this->invoice
                ->fresh()
                ->outstandingAmount()
        );

        $this->assertSame(
            2000,
            $this->securityDeposit
                ->fresh()
                ->balance()
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 2001,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            1
        );
    }

    public function test_application_posts_balanced_journal_when_runtime_enabled(): void
    {
        $this->completeCutover();

        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 2500,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertCreated();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::EVENT_SECURITY_DEPOSIT_APPLIED
                )
                ->sole();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            2500,
            (int) $entry
                ->lines
                ->sum('debit_amount')
        );

        $this->assertSame(
            2500,
            (int) $entry
                ->lines
                ->sum('credit_amount')
        );
    }

    public function test_journal_failure_rolls_back_application_fund_movement_and_activity(): void
    {
        $this->completeCutover();

        $this->mock(
            JournalPostingService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('post')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Forced Security Deposit application Journal failure.'
                        )
                    );
            }
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->postJson(
                "/api/leases/{$this->lease->id}/security-deposit/apply",
                [
                    'invoice_id' => $this->invoice->id,

                    'amount' => 2500,

                    'transaction_date' => '2026-08-19',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'security_deposit_applications',
            0
        );

        /*
         * Only original Security Deposit funding survives.
         */
        $this->assertSame(
            1,
            TenantFundTransaction::count()
        );

        $this->assertSame(
            6000,
            $this->securityDeposit
                ->fresh()
                ->balance()
        );

        $this->assertSame(
            10000,
            $this->invoice
                ->fresh()
                ->outstandingAmount()
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    private function completeCutover(): void
    {
        AccountingCutover::create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' => '2026-08-19',

            'status' => AccountingCutover::STATUS_COMPLETED,

            'position_count' => 0,

            'journal_entry_count' => 0,

            'completed_at' => now(),

            'metadata' => [],
        ]);
    }

    private function financialContext(
        string $suffix = 'main'
    ): array {
        $building =
            Building::create([
                'name' => 'Security Deposit Application Building '
                    .$suffix,
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Unit '.$suffix,
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Security Deposit Application Tenant '
                    .$suffix,

                'phone' => '0200006'
                    .str_pad(
                        (string) random_int(
                            0,
                            999
                        ),
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),

                'email' => 'security-deposit-application-'
                    .$suffix
                    .'-'
                    .uniqid()
                    .'@example.test',
            ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,

                'tenant_id' => $tenant->id,

                'start_date' => '2026-01-01',

                'rent_amount' => 10000,

                'payment_frequency' => 'monthly',

                'due_day' => 1,

                'vat_rate' => 0,

                'management_fee_type' => 'none',

                'management_fee_value' => 0,

                'agent_commission_amount' => 0,

                'status' => 'active',
            ]);

        $invoice =
            Invoice::create([
                'lease_id' => $lease->id,

                'invoice_number' => 'INV-SDA-'
                    .strtoupper($suffix)
                    .'-'
                    .uniqid(),

                'type' => 'rent',

                'period_start' => '2026-08-01',

                'period_end' => '2026-08-31',

                'issue_date' => '2026-08-01',

                'due_date' => '2026-08-01',

                'status' => 'issued',

                'total_amount' => 10000,

                'vat_rate' => 0,

                'net_amount' => 10000,

                'vat_amount' => 0,

                'proration_amount' => null,
            ]);

        $securityDeposit =
            TenantFundAccount::create([
                'lease_id' => $lease->id,

                'type' => 'security_deposit',

                'status' => 'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $securityDeposit->id,

            'direction' => 'credit',

            'category' => 'deposit_funding',

            'amount' => 6000,

            'transaction_date' => '2026-08-01',

            'notes' => 'Security Deposit test funding.',
        ]);

        return [
            'building' => $building,

            'unit' => $unit,

            'tenant' => $tenant,

            'lease' => $lease,

            'invoice' => $invoice,

            'security_deposit' => $securityDeposit,
        ];
    }
}
