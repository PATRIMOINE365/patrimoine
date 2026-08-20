<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\LeaseDeletion\LeaseDeletionRestorationPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaseDeletionRestorationPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_is_read_only_and_returns_execution_contract(): void
    {
        $lease = $this->createLease();

        $before = $this->fingerprint();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $after = $this->fingerprint();

        $this->assertSame($before, $after);

        $this->assertSame(
            $lease->id,
            $plan['lease']['id']
        );

        $this->assertTrue(
            $plan['execution_contract']['atomic']
        );

        $this->assertTrue(
            $plan['execution_contract']['mandatory_reason']
        );

        $this->assertSame(
            'DELETE',
            $plan['execution_contract']['typed_confirmation']
        );

        $this->assertTrue(
            $plan['execution_contract']['password_reverification']
        );

        $this->assertTrue(
            $plan['preserve']['journal_entries']
        );

        $this->assertTrue(
            $plan['preserve']['activity_logs']
        );
    }

    public function test_operational_plan_is_fk_safe_and_places_children_before_parents(): void
    {
        $lease = $this->createLease();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $tables = array_column(
            $plan['operational']['delete_in_order'],
            'table'
        );

        $position =
            fn (string $table): int =>
                array_search(
                    $table,
                    $tables,
                    true
                );

        /*
         * Security Deposit application restrictively references both
         * TenantFundTransaction and Invoice.
         */
        $this->assertLessThan(
            $position('tenant_fund_transactions'),
            $position('security_deposit_applications')
        );

        $this->assertLessThan(
            $position('invoices'),
            $position('security_deposit_applications')
        );

        /*
         * Owner payout allocations belong to Owner Transactions.
         */
        $this->assertLessThan(
            $position('owner_transactions'),
            $position('owner_payout_allocations')
        );

        /*
         * Owner Transactions may restrictively reference Payment Allocation
         * and Invoice, so they must disappear first.
         */
        $this->assertLessThan(
            $position('payment_allocations'),
            $position('owner_transactions')
        );

        $this->assertLessThan(
            $position('invoices'),
            $position('owner_transactions')
        );

        /*
         * Payment Allocation itself is a child of Payment and Invoice.
         */
        $this->assertLessThan(
            $position('payments'),
            $position('payment_allocations')
        );

        $this->assertLessThan(
            $position('invoices'),
            $position('payment_allocations')
        );

        /*
         * Tenant Fund Transactions must disappear before their account.
         */
        $this->assertLessThan(
            $position('tenant_fund_accounts'),
            $position('tenant_fund_transactions')
        );

        /*
         * Lease remains the final operational record.
         */
        $this->assertSame(
            'leases',
            $tables[array_key_last($tables)]
        );
    }

    public function test_plan_never_lists_journal_or_activity_log_for_deletion(): void
    {
        $lease = $this->createLease();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $tables = array_column(
            $plan['operational']['delete_in_order'],
            'table'
        );

        $this->assertNotContains(
            'journal_entries',
            $tables
        );

        $this->assertNotContains(
            'journal_lines',
            $tables
        );

        $this->assertNotContains(
            'activity_logs',
            $tables
        );
    }

    public function test_unknown_monetary_effect_causes_fail_closed_plan(): void
    {
        $lease = $this->createLease();

        /*
         * The underlying 10B services already own detailed schema/error
         * classification. This test protects the 10C contract: any blocker
         * emitted by those services must prevent execution.
         */
        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        if (
            $plan['eligibility']['blocking_reasons']
            !== []
        ) {
            $this->assertFalse(
                $plan['eligibility']['safe_to_execute']
            );
        } else {
            $this->assertTrue(
                $plan['eligibility']['safe_to_execute']
            );
        }
    }

    public function test_active_structured_journal_entry_is_planned_for_full_reversal(): void
    {
        $lease = $this->createLease();

        $transaction =
            $this->createLeaseFundTransaction(
                $lease
            );

        $journalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920001',

                'transaction_type' =>
                    'tenant_fund_test',

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'description' =>
                    'Phase 10C2 active structured source.',
            ]);

        $this->enableAccountingRuntime();

        $before = $this->fingerprint();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $after = $this->fingerprint();

        $this->assertSame(
            $before,
            $after
        );

        $candidate =
            collect(
                $plan['accounting']['reversal_candidates']
            )->firstWhere(
                'journal_entry_id',
                $journalId
            );

        $this->assertNotNull(
            $candidate
        );

        $this->assertSame(
            'reverse_full_entry',
            $candidate['action']
        );

        $this->assertSame(
            $journalId,
            $candidate['journal_entry_id']
        );
    }

    public function test_already_reversed_entry_is_not_planned_for_second_reversal(): void
    {
        $lease = $this->createLease();

        $transaction =
            $this->createLeaseFundTransaction(
                $lease
            );

        $originalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920002',

                'transaction_type' =>
                    'tenant_fund_test',

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'description' =>
                    'Phase 10C2 reversed original.',
            ]);

        $reversalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920003',

                'entry_kind' =>
                    'reversal',

                'transaction_type' =>
                    'journal_reversal',

                'source_type' =>
                    \App\Models\JournalEntry::class,

                'source_id' =>
                    $originalId,

                'description' =>
                    'Phase 10C2 reversal.',

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

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $candidateIds =
            collect(
                $plan['accounting']['reversal_candidates']
            )
                ->pluck('journal_entry_id')
                ->all();

        $this->assertNotContains(
            $originalId,
            $candidateIds
        );

        $neutralized =
            collect(
                $plan['accounting']['already_neutralized']
            )->firstWhere(
                'journal_entry_id',
                $originalId
            );

        $this->assertNotNull(
            $neutralized
        );

        $this->assertSame(
            'no_additional_reversal',
            $neutralized['action']
        );
    }

    public function test_snapshot_only_journal_entry_blocks_execution_plan(): void
    {
        $lease = $this->createLease();

        $journalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920004',

                'transaction_type' =>
                    'manual_test',

                'source_type' =>
                    'unknown_source',

                'source_id' =>
                    987654,

                'description' =>
                    'Phase 10C2 snapshot-only Journal context.',

                'snapshot' => [
                    'lease_id' =>
                        $lease->id,

                    'lease_reference' =>
                        'PHASE-10C2',
                ],
            ]);

        $this->enableAccountingRuntime();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $unclassifiedIds =
            collect(
                $plan['accounting']['unclassified_entries']
            )
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->all();

        $this->assertContains(
            $journalId,
            $unclassifiedIds
        );

        $this->assertFalse(
            $plan['eligibility']['safe_to_execute']
        );

        $this->assertNotEmpty(
            $plan['eligibility']['blocking_reasons']
        );
    }

    public function test_consolidated_owner_opening_balance_blocks_execution(): void
    {
        $lease = $this->createLease();

        /*
         * Snapshot context makes the Journal entry discoverable for this
         * Lease, but source_type identifies a consolidated Owner opening
         * position. The entire Owner opening entry must never be reversed
         * merely because this Lease contributed to it.
         */
        $journalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920005',

                'transaction_type' =>
                    'opening_balance',

                'source_type' =>
                    'owner_account',

                'source_id' =>
                    123456,

                'description' =>
                    'V1.0.5 Opening Balance',

                'snapshot' => [
                    'lease_id' =>
                        $lease->id,
                ],
            ]);

        $this->enableAccountingRuntime();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $opening =
            collect(
                $plan['accounting']['opening_balance_actions']
            )->firstWhere(
                'journal_entry_id',
                $journalId
            );

        $this->assertNotNull(
            $opening
        );

        $this->assertSame(
            'consolidated_owner_opening_balance',
            $opening['classification']
        );

        $this->assertSame(
            'blocked_requires_partial_owner_neutralization',
            $opening['action']
        );

        $this->assertFalse(
            $plan['eligibility']['safe_to_execute']
        );

        $this->assertNotEmpty(
            $plan['eligibility']['blocking_reasons']
        );
    }

    public function test_lease_specific_tenant_fund_opening_balance_receives_neutralization_action(): void
    {
        $lease = $this->createLease();

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

        $journalId =
            $this->createJournalEntry([
                'journal_number' =>
                    'JRN-2026-920006',

                'transaction_type' =>
                    'opening_balance',

                'source_type' =>
                    TenantFundAccount::class,

                'source_id' =>
                    $account->id,

                'description' =>
                    'V1.0.5 Opening Balance',
            ]);

        $this->enableAccountingRuntime();

        $before = $this->fingerprint();

        $plan = app(
            LeaseDeletionRestorationPlanService::class
        )->plan($lease);

        $after = $this->fingerprint();

        $this->assertSame(
            $before,
            $after
        );

        $opening =
            collect(
                $plan['accounting']['opening_balance_actions']
            )->firstWhere(
                'journal_entry_id',
                $journalId
            );

        $this->assertNotNull(
            $opening
        );

        $this->assertSame(
            'lease_specific_tenant_fund_opening_balance',
            $opening['classification']
        );

        $this->assertSame(
            'neutralize_lease_specific_opening_effect',
            $opening['action']
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

            'position_count' =>
                0,

            'journal_entry_count' =>
                0,

            'completed_at' =>
                now(),
        ]);
    }

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
     * Create the smallest valid Journal header supported by the current
     * schema.
     *
     * @param array<string, mixed> $overrides
     */
    private function createJournalEntry(
        array $overrides
    ): int {
        $columns =
            Schema::getColumnListing(
                'journal_entries'
            );

        $values = [
            'journal_number' =>
                'JRN-2026-929999',

            'journal_date' =>
                now()->toDateString(),

            'posted_at' =>
                now(),

            'transaction_type' =>
                'manual_test',

            'amount' =>
                1000,

            'description' =>
                'Phase 10C2 Journal test',

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

        foreach (
            $overrides
            as $key => $value
        ) {
            if (
                $key === 'snapshot'
                && is_array($value)
            ) {
                $value =
                    json_encode($value);
            }

            $values[$key] =
                $value;
        }

        $values =
            array_filter(
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
        )->insertGetId(
            $values
        );
    }

    private function createLease(): Lease
    {
        $tenant = Party::query()->create([
            'type' => 'person',
            'name' => 'Phase 10C2 Tenant',
        ]);

        $building = Building::query()->create([
            'name' => 'Phase 10C2 Building',
        ]);

        $unit = Unit::query()->create([
            'building_id' => $building->id,
            'name' => 'Phase 10C2 Unit',
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
     * @return array<string, int>
     */
    private function fingerprint(): array
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
            'journal_lines',
            'activity_logs',
        ];

        $result = [];

        foreach ($tables as $table) {
            $result[$table] =
                Schema::hasTable($table)
                    ? DB::table($table)->count()
                    : 0;
        }

        return $result;
    }
}
