<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\LeaseDeletion\LeaseDeletionImpactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaseDeletionImpactServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_impact_calculator_is_read_only(): void
    {
        $lease = $this->createLease();

        $before = $this->databaseFingerprint();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $after = $this->databaseFingerprint();

        $this->assertSame($before, $after);

        $this->assertSame(
            $lease->id,
            $impact['lease']['id']
        );

        $this->assertArrayHasKey(
            'direct_dependencies',
            $impact
        );

        $this->assertArrayHasKey(
            'indirect_dependencies',
            $impact
        );

        $this->assertArrayHasKey(
            'journal',
            $impact
        );

        $this->assertArrayHasKey(
            'blocking_reasons',
            $impact
        );

        $this->assertArrayHasKey(
            'safe_to_delete',
            $impact
        );
    }

    public function test_direct_lease_dependencies_are_enumerated(): void
    {
        $lease = $this->createLease();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        foreach (
            [
                'invoices',
                'owner_transactions',
                'payments',
                'tenant_fund_accounts',
            ]
            as $table
        ) {
            if (
                ! array_key_exists(
                    $table,
                    $impact['direct_dependencies']
                )
            ) {
                continue;
            }

            $this->assertArrayHasKey(
                'count',
                $impact['direct_dependencies'][$table]
            );

            $this->assertArrayHasKey(
                'ids',
                $impact['direct_dependencies'][$table]
            );
        }
    }

    public function test_impact_calculator_fails_closed_when_it_reports_unknown_dependencies(): void
    {
        $lease = $this->createLease();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        if (
            $impact['unknown_dependencies'] !== []
        ) {
            $this->assertFalse(
                $impact['safe_to_delete']
            );

            $this->assertNotEmpty(
                $impact['blocking_reasons']
            );

            return;
        }

        $this->assertIsBool(
            $impact['safe_to_delete']
        );
    }

    public function test_journal_discovery_does_not_modify_journal(): void
    {
        $lease = $this->createLease();

        $beforeEntries =
            $this->tableCountIfExists(
                'journal_entries'
            );

        $beforeTransactions =
            $this->tableCountIfExists(
                'journal_transactions'
            );

        $beforeLines =
            $this->tableCountIfExists(
                'journal_lines'
            );

        app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $this->assertSame(
            $beforeEntries,
            $this->tableCountIfExists(
                'journal_entries'
            )
        );

        $this->assertSame(
            $beforeTransactions,
            $this->tableCountIfExists(
                'journal_transactions'
            )
        );

        $this->assertSame(
            $beforeLines,
            $this->tableCountIfExists(
                'journal_lines'
            )
        );
    }


    public function test_impact_reports_accounting_runtime_as_disabled_before_cutover(): void
    {
        $lease = $this->createLease();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $this->assertArrayHasKey(
            'accounting',
            $impact
        );

        $this->assertFalse(
            $impact['accounting']['runtime_enabled']
        );

        $this->assertTrue(
            $impact['accounting']['impact_classified']
        );
    }

    public function test_impact_reports_accounting_runtime_as_enabled_after_completed_cutover(): void
    {
        $lease = $this->createLease();

        AccountingCutover::query()->create([
            'cutover_key' =>
                AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' =>
                now()->toDateString(),

            'status' =>
                AccountingCutover::STATUS_COMPLETED,

            'position_count' =>
                0,

            'journal_entry_count' =>
                0,

            'completed_at' =>
                now(),
        ]);

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $this->assertTrue(
            $impact['accounting']['runtime_enabled']
        );

        $this->assertArrayHasKey(
            'impact_classified',
            $impact['accounting']
        );
    }


    public function test_structured_lease_journal_entry_is_classified_as_active(): void
    {
        $lease = $this->createLease();

        $transaction =
            $this->createLeaseFundTransaction(
                $lease
            );

        $journalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-910001',

                'transaction_type' =>
                    'tenant_fund_test',

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'description' =>
                    'Phase 10B1 structured Tenant Fund transaction.',
            ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $activeIds = collect(
            $impact['journal']['active_entries']
        )
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        $this->assertContains(
            $journalId,
            $activeIds
        );

        $this->assertSame(
            [],
            $impact['journal']['unclassified_entries']
        );

        $this->assertSame(
            [],
            $impact['journal']['shared_entries']
        );

        $this->assertTrue(
            $impact['accounting']['impact_classified']
        );
    }

    public function test_snapshot_only_lease_journal_attribution_fails_closed(): void
    {
        $lease = $this->createLease();

        $journalId = $this->createJournalEntry([
            'journal_number' => 'JRN-2026-910002',
            'transaction_type' => 'manual_test',
            'source_type' => 'unknown_source',
            'source_id' => 987654,
            'description' => 'Phase 10B1 snapshot-only attribution',
            'snapshot' => [
                'lease_id' =>
                    $lease->id,

                'lease_reference' =>
                    'PHASE-10B1',
            ],
        ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $unclassifiedIds = collect(
            $impact['journal']['unclassified_entries']
        )->pluck('id')->map(
            fn ($id) => (int) $id
        )->all();

        $this->assertContains(
            $journalId,
            $unclassifiedIds
        );

        $this->assertFalse(
            $impact['accounting']['impact_classified']
        );

        $this->assertFalse(
            $impact['safe_to_delete']
        );

        $this->assertNotEmpty(
            $impact['blocking_reasons']
        );
    }

    /**
     * Regression: rent receipts, owner entitlements and management fees are
     * all posted with PaymentAllocation as their structured source. While
     * allocations were missing from the source inventory those entries could
     * only ever be discovered by snapshot, so every Lease that had received a
     * rent payment was permanently undeletable.
     */
    public function test_payment_allocation_sourced_journal_entry_is_classified_as_active(): void
    {
        $lease = $this->createLease();

        $allocation =
            $this->createLeasePaymentAllocation(
                $lease
            );

        $journalId = $this->createJournalEntry([
            'journal_number' => 'JRN-2026-910010',
            'transaction_type' => 'rent_receipt',

            'source_type' =>
                \App\Models\PaymentAllocation::class,

            'source_id' =>
                $allocation->id,

            'description' =>
                'Rent receipt allocation.',

            'snapshot' => [
                'payment_allocation_id' =>
                    $allocation->id,

                'lease_id' =>
                    $lease->id,
            ],
        ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $activeIds = collect(
            $impact['journal']['active_entries']
        )->pluck('id')->map(
            fn ($id) => (int) $id
        )->all();

        $this->assertContains(
            $journalId,
            $activeIds
        );

        $this->assertSame(
            [],
            $impact['journal']['unclassified_entries']
        );

        $this->assertTrue(
            $impact['accounting']['impact_classified']
        );
    }

    /**
     * Regression: snapshot discovery matches on a substring, so
     * '"lease_id":1' also matches leases 10, 19 and 100. A neighbouring
     * Lease's posting must never enter this Lease's impact set.
     */
    public function test_snapshot_lease_id_prefix_does_not_match_another_lease(): void
    {
        $lease = $this->createLease();

        $neighbourLeaseId =
            (int) ($lease->id . '9');

        $journalId = $this->createJournalEntry([
            'journal_number' => 'JRN-2026-910011',
            'transaction_type' => 'manual_test',
            'source_type' => 'unknown_source',
            'source_id' => 987654,

            'description' =>
                'Posting belonging to a different Lease.',

            'snapshot' => [
                'lease_id' =>
                    $neighbourLeaseId,
            ],
        ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $discoveredIds = collect(
            $impact['journal']['entries']
        )->pluck('id')->map(
            fn ($id) => (int) $id
        )->all();

        $this->assertNotContains(
            $journalId,
            $discoveredIds
        );

        $this->assertSame(
            [],
            $impact['journal']['unclassified_entries']
        );
    }

    public function test_shared_owner_payout_journal_attribution_fails_closed(): void
    {
        $lease = $this->createLease();

        $journalId = $this->createJournalEntry([
            'journal_number' => 'JRN-2026-910003',
            'transaction_type' => 'owner_payout',
            'source_type' => 'owner_payout',
            'source_id' => 123456,
            'description' => 'Phase 10B1 shared owner payout',
            'snapshot' => [
                'lease_id' =>
                    $lease->id,
            ],
        ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $sharedIds = collect(
            $impact['journal']['shared_entries']
        )->pluck('id')->map(
            fn ($id) => (int) $id
        )->all();

        $this->assertContains(
            $journalId,
            $sharedIds
        );

        $this->assertFalse(
            $impact['accounting']['impact_classified']
        );

        $this->assertFalse(
            $impact['safe_to_delete']
        );
    }

    public function test_reversed_structured_journal_entry_is_not_active(): void
    {
        $lease = $this->createLease();

        $transaction =
            $this->createLeaseFundTransaction(
                $lease
            );

        $originalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-910004',

                'transaction_type' =>
                    'tenant_fund_test',

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'description' =>
                    'Phase 10B1 reversed structured original.',
            ]);

        $reversalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-910005',

                'entry_kind' =>
                    'reversal',

                'transaction_type' =>
                    'journal_reversal',

                'source_type' =>
                    \App\Models\JournalEntry::class,

                'source_id' =>
                    $originalId,

                'description' =>
                    'Phase 10B1 reversal.',

                'reversal_of_id' =>
                    $originalId,
            ]);

        DB::table('journal_entries')
            ->where(
                'id',
                $originalId
            )
            ->update([
                'reversed_by_id' =>
                    $reversalId,

                'updated_at' =>
                    now(),
            ]);

        $this->enableAccountingRuntime();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $activeIds = collect(
            $impact['journal']['active_entries']
        )
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        $reversedIds = collect(
            $impact['journal']['already_reversed_entries']
        )
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        $this->assertNotContains(
            $originalId,
            $activeIds
        );

        $this->assertContains(
            $originalId,
            $reversedIds
        );

        if (
            collect(
                $impact['journal']['entries']
            )
                ->pluck('id')
                ->contains(
                    $reversalId
                )
        ) {
            $this->assertContains(
                $reversalId,
                $reversedIds
            );
        }
    }

    public function test_journal_attribution_classification_remains_read_only(): void
    {
        $lease = $this->createLease();

        $this->createJournalEntry([
            'journal_number' => 'JRN-2026-910006',
            'transaction_type' => 'manual_test',
            'source_type' => 'unknown_source',
            'source_id' => 777777,
            'description' => 'Phase 10B1 read-only classification',
            'snapshot' => [
                'lease_id' =>
                    $lease->id,
            ],
        ]);

        $this->enableAccountingRuntime();

        $before = $this->databaseFingerprint();

        app(
            LeaseDeletionImpactService::class
        )->calculate($lease);

        $after = $this->databaseFingerprint();

        $this->assertSame(
            $before,
            $after
        );
    }

    private function enableAccountingRuntime(): void
    {
        AccountingCutover::query()->create([
            'cutover_key' =>
                AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' =>
                now()->toDateString(),

            'status' =>
                AccountingCutover::STATUS_COMPLETED,

            'position_count' => 0,

            'journal_entry_count' => 0,

            'completed_at' => now(),
        ]);
    }

    /**
     * Create the smallest valid Journal header supported by the current
     * schema. Optional fields are added only when the schema contains them.
     *
     * @param array<string, mixed> $overrides
     */
    private function createLeaseFundTransaction(
        Lease $lease
    ): TenantFundTransaction {
        $account =
            TenantFundAccount::query()
                ->create([
                    'lease_id' =>
                        $lease->id,

                    'type' =>
                        'rent_reserve',

                    'status' =>
                        'active',
                ]);

        return TenantFundTransaction::query()
            ->create([
                'tenant_fund_account_id' =>
                    $account->id,

                'direction' =>
                    'credit',

                'category' =>
                    'reserve_funding',

                'amount' =>
                    1000,

                'transaction_date' =>
                    now()->toDateString(),
            ]);
    }

    /**
     * Create an Invoice, a Payment and the allocation that links them,
     * all belonging to the supplied Lease.
     */
    private function createLeasePaymentAllocation(
        Lease $lease
    ): \App\Models\PaymentAllocation {
        $invoice =
            \App\Models\Invoice::query()->create([
                'lease_id' => $lease->id,
                'invoice_number' => 'INV-910010',
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-05',
                'status' => 'issued',
                'total_amount' => 1000,
                'vat_rate' => 0,
                'net_amount' => 1000,
                'vat_amount' => 0,
            ]);

        $payment =
            \App\Models\Payment::query()->create([
                'lease_id' => $lease->id,
                'amount' => 1000,
                'payment_date' => '2026-01-05',
                'payment_method' => 'bank_transfer',
            ]);

        return \App\Models\PaymentAllocation::query()
            ->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => 1000,
            ]);
    }

    private function createJournalEntry(
        array $overrides
    ): int {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing(
            'journal_entries'
        );

        $values = [
            'organisation_id' =>
                $this->testOrganisation->id,

            'journal_number' =>
                'JRN-2026-919999',

            'journal_date' =>
                now()->toDateString(),

            'posted_at' =>
                now(),

            'transaction_type' =>
                'manual_test',

            'amount' =>
                1000,

            'description' =>
                'Phase 10B1 Journal test',

            'source_type' =>
                'unknown_source',

            'source_id' =>
                1,

            'snapshot' =>
                json_encode([]),

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        foreach ($overrides as $key => $value) {
            if ($key === 'snapshot' && is_array($value)) {
                $value = json_encode($value);
            }

            $values[$key] = $value;
        }

        $values = array_filter(
            $values,
            fn ($value, $key) =>
                in_array(
                    $key,
                    $columns,
                    true
                ),
            ARRAY_FILTER_USE_BOTH
        );

        return (int) DB::table(
            'journal_entries'
        )->insertGetId($values);
    }

    private function createLease(): Lease
    {
        $tenant = Party::query()->create([
            'type' => 'person',
            'name' => 'Phase 10B1 Tenant',
        ]);

        $building = Building::query()->create([
            'name' => 'Phase 10B1 Building',
        ]);

        $unit = Unit::query()->create([
            'building_id' => $building->id,
            'name' => 'Phase 10B1 Unit',
        ]);

        return Lease::query()->create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'draft',
            'rent_amount' => 1000,
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

    /**
     * Fingerprint tables relevant to destructive Lease deletion.
     *
     * @return array<string, int>
     */
    private function databaseFingerprint(): array
    {
        $tables = [
            'leases',
            'invoices',
            'payments',
            'payment_allocations',
            'tenant_fund_accounts',
            'tenant_fund_transactions',
            'owner_transactions',
            'owner_payout_allocations',
            'security_deposit_applications',
            'security_deposit_deductions',
            'security_deposit_settlements',
            'withdrawal_receipts',
            'journal_entries',
            'journal_transactions',
            'journal_lines',
            'activity_logs',
        ];

        $result = [];

        foreach ($tables as $table) {
            $result[$table] =
                $this->tableCountIfExists($table);
        }

        return $result;
    }

    private function tableCountIfExists(
        string $table
    ): int {
        if (
            ! \Illuminate\Support\Facades\Schema::hasTable(
                $table
            )
        ) {
            return 0;
        }

        return DB::table($table)->count();
    }

    /**
     * V1.0.50: a lease that was never paid or invoiced has nothing to
     * look for, and must find nothing. With both id lists empty the
     * allocation query used to run unconstrained and return every
     * allocation on the installation — every unpaid draft was
     * undeletable for "crossing lease boundaries", and its preview
     * listed other leases' (and other organisations') allocation ids.
     */
    public function test_a_lease_without_payments_sees_no_allocations_at_all(): void
    {
        $paid = $this->createLease();

        $allocation =
            $this->createLeasePaymentAllocation($paid);

        $draft = $this->createLease();

        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($draft);

        $this->assertSame(
            0,
            $impact['indirect_dependencies']['payment_allocations']['count']
        );

        $this->assertSame(
            [],
            $impact['indirect_dependencies']['payment_allocations']['ids']
        );

        $this->assertSame(
            [],
            array_values(
                array_filter(
                    $impact['unknown_dependencies'],
                    fn (array $dependency): bool =>
                        $dependency['type'] === 'cross_lease_payment_allocation'
                )
            )
        );

        /*
         * The paid lease still sees its own allocation.
         */
        $impact = app(
            LeaseDeletionImpactService::class
        )->calculate($paid);

        $this->assertSame(
            [$allocation->id],
            $impact['indirect_dependencies']['payment_allocations']['ids']
        );
    }
}
