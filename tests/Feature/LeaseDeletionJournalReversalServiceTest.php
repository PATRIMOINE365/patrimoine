<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\LeaseDeletion\LeaseDeletionJournalReversalService;
use App\Services\LeaseDeletion\LeaseDeletionRestorationPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LeaseDeletionJournalReversalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_reason_is_required_before_any_mutation(): void
    {
        $lease = $this->createLease();

        $before =
            $this->fingerprint();

        try {
            app(
                LeaseDeletionJournalReversalService::class
            )->execute(
                $lease,
                '   '
            );

            $this->fail(
                'Expected empty reversal reason to be rejected.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertTrue(true);
        }

        $this->assertSame(
            $before,
            $this->fingerprint()
        );
    }

    public function test_unsafe_plan_cannot_execute(): void
    {
        $lease = $this->createLease();

        $planner =
            Mockery::mock(
                LeaseDeletionRestorationPlanService::class
            );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn([
                'eligibility' => [
                    'safe_to_execute' =>
                        false,

                    'blocking_reasons' => [
                        'Unsafe test plan.',
                    ],
                ],

                'accounting' => [
                    'shared_entries' => [],
                    'unclassified_entries' => [],
                    'opening_balance_actions' => [],
                    'reversal_candidates' => [],
                ],
            ]);

        $service =
            new LeaseDeletionJournalReversalService(
                $planner,
                app(
                    JournalReversalService::class
                )
            );

        $before =
            $this->fingerprint();

        $this->expectException(
            RuntimeException::class
        );

        try {
            $service->execute(
                $lease,
                'Unsafe plan test.'
            );
        } finally {
            $this->assertSame(
                $before,
                $this->fingerprint()
            );
        }
    }

    public function test_one_candidate_creates_one_exact_reversal(): void
    {
        $lease = $this->createLease();

        $original =
            $this->postLeaseJournal(
                $lease,
                '10d2-single'
            );

        $service =
            $this->serviceForCandidates(
                $lease,
                [
                    $this->candidate(
                        $original
                    ),
                ]
            );

        $result =
            $service->execute(
                $lease,
                'Correct destructive deletion.'
            );

        $original->refresh();

        $this->assertSame(
            1,
            $result['reversal_count']
        );

        $this->assertNotNull(
            $original->reversed_by_id
        );

        $reversal =
            JournalEntry::query()
                ->with('lines')
                ->findOrFail(
                    $original->reversed_by_id
                );

        $this->assertSame(
            $original->id,
            $reversal->reversal_of_id
        );

        $this->assertSame(
            $original->debitTotal(),
            $reversal->creditTotal()
        );

        $this->assertSame(
            $original->creditTotal(),
            $reversal->debitTotal()
        );

        $this->assertStringContainsString(
            'Correct destructive deletion.',
            (string) $reversal
                ->reversal_reason
        );

        $this->assertTrue(
            $reversal->isBalanced()
        );
    }

    public function test_already_reversed_entry_is_skipped_without_duplicate(): void
    {
        $lease = $this->createLease();

        $original =
            $this->postLeaseJournal(
                $lease,
                '10d2-already'
            );

        $candidate =
            $this->candidate(
                $original
            );

        app(
            JournalReversalService::class
        )->reverse(
            $original,
            'Existing reversal.'
        );

        $entryCount =
            JournalEntry::query()->count();

        $service =
            $this->serviceForCandidates(
                $lease,
                [
                    $candidate,
                ]
            );

        $result =
            $service->execute(
                $lease,
                'Retry-safe deletion.'
            );

        $this->assertSame(
            0,
            $result['reversal_count']
        );

        $this->assertSame(
            1,
            $result[
                'already_neutralized_count'
            ]
        );

        $this->assertSame(
            $entryCount,
            JournalEntry::query()->count()
        );
    }

    public function test_multiple_candidates_are_reversed(): void
    {
        $lease = $this->createLease();

        $first =
            $this->postLeaseJournal(
                $lease,
                '10d2-multi-1'
            );

        $second =
            $this->postLeaseJournal(
                $lease,
                '10d2-multi-2'
            );

        $service =
            $this->serviceForCandidates(
                $lease,
                [
                    $this->candidate($first),
                    $this->candidate($second),
                ]
            );

        $result =
            $service->execute(
                $lease,
                'Reverse both.'
            );

        $this->assertSame(
            2,
            $result['reversal_count']
        );

        $this->assertTrue(
            $first->fresh()->isReversed()
        );

        $this->assertTrue(
            $second->fresh()->isReversed()
        );
    }

    public function test_batch_is_atomic_when_later_reversal_fails(): void
    {
        $lease = $this->createLease();

        $first =
            $this->postLeaseJournal(
                $lease,
                '10d2-atomic-1'
            );

        $second =
            $this->postLeaseJournal(
                $lease,
                '10d2-atomic-2'
            );

        $real =
            app(
                JournalReversalService::class
            );

        $mock =
            Mockery::mock(
                JournalReversalService::class
            );

        $calls = 0;

        $mock->shouldReceive('reverse')
            ->twice()
            ->andReturnUsing(
                function (
                    JournalEntry $entry,
                    string $reason,
                    ?int $actorUserId = null
                ) use (
                    &$calls,
                    $real
                ) {
                    $calls++;

                    if ($calls === 2) {
                        throw new RuntimeException(
                            'Forced second reversal failure.'
                        );
                    }

                    return $real->reverse(
                        $entry,
                        $reason,
                        $actorUserId
                    );
                }
            );

        $service =
            new LeaseDeletionJournalReversalService(
                $this->plannerForCandidates(
                    [
                        $this->candidate($first),
                        $this->candidate($second),
                    ]
                ),
                $mock
            );

        $beforeEntries =
            JournalEntry::query()->count();

        try {
            $service->execute(
                $lease,
                'Atomic rollback test.'
            );

            $this->fail(
                'Expected forced second reversal failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'Forced second reversal failure.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            $beforeEntries,
            JournalEntry::query()->count()
        );

        $this->assertNull(
            $first->fresh()
                ->reversed_by_id
        );

        $this->assertNull(
            $second->fresh()
                ->reversed_by_id
        );
    }

    public function test_shared_or_unclassified_plan_causes_zero_mutation(): void
    {
        $lease = $this->createLease();

        $original =
            $this->postLeaseJournal(
                $lease,
                '10d2-blocked'
            );

        $planner =
            Mockery::mock(
                LeaseDeletionRestorationPlanService::class
            );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn([
                'eligibility' => [
                    'safe_to_execute' =>
                        true,

                    'blocking_reasons' => [],
                ],

                'accounting' => [
                    'shared_entries' => [
                        ['id' => 999],
                    ],

                    'unclassified_entries' => [],

                    'opening_balance_actions' => [],

                    'reversal_candidates' => [
                        $this->candidate(
                            $original
                        ),
                    ],
                ],
            ]);

        $service =
            new LeaseDeletionJournalReversalService(
                $planner,
                app(
                    JournalReversalService::class
                )
            );

        $before =
            $this->fingerprint();

        try {
            $service->execute(
                $lease,
                'Blocked accounting.'
            );

            $this->fail(
                'Expected shared accounting blocker.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertTrue(true);
        }

        $this->assertSame(
            $before,
            $this->fingerprint()
        );
    }

    public function test_blocked_opening_balance_action_causes_zero_mutation(): void
    {
        $lease = $this->createLease();

        $planner =
            Mockery::mock(
                LeaseDeletionRestorationPlanService::class
            );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn([
                'eligibility' => [
                    'safe_to_execute' =>
                        true,

                    'blocking_reasons' => [],
                ],

                'accounting' => [
                    'shared_entries' => [],
                    'unclassified_entries' => [],
                    'reversal_candidates' => [],

                    'opening_balance_actions' => [
                        [
                            'action' =>
                                'blocked_requires_partial_owner_neutralization',
                        ],
                    ],
                ],
            ]);

        $service =
            new LeaseDeletionJournalReversalService(
                $planner,
                app(
                    JournalReversalService::class
                )
            );

        $before =
            $this->fingerprint();

        try {
            $service->execute(
                $lease,
                'Opening balance blocker.'
            );

            $this->fail(
                'Expected blocked opening balance.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertTrue(true);
        }

        $this->assertSame(
            $before,
            $this->fingerprint()
        );
    }

    public function test_service_does_not_delete_operational_or_audit_records(): void
    {
        $lease = $this->createLease();

        $original =
            $this->postLeaseJournal(
                $lease,
                '10d2-preserve'
            );

        $before = [
            'leases' =>
                DB::table('leases')->count(),

            'journal_entries' =>
                DB::table(
                    'journal_entries'
                )->count(),

            'activity_logs' =>
                DB::table(
                    'activity_logs'
                )->count(),
        ];

        $service =
            $this->serviceForCandidates(
                $lease,
                [
                    $this->candidate(
                        $original
                    ),
                ]
            );

        $service->execute(
            $lease,
            'Accounting only.'
        );

        $this->assertSame(
            $before['leases'],
            DB::table('leases')->count()
        );

        $this->assertSame(
            $before['activity_logs'],
            DB::table(
                'activity_logs'
            )->count()
        );

        /*
         * Journal history grows because reversal is append-only.
         */
        $this->assertSame(
            $before['journal_entries'] + 1,
            DB::table(
                'journal_entries'
            )->count()
        );
    }

    private function serviceForCandidates(
        Lease $lease,
        array $candidates
    ): LeaseDeletionJournalReversalService {
        return new LeaseDeletionJournalReversalService(
            $this->plannerForCandidates(
                $candidates
            ),
            app(
                JournalReversalService::class
            )
        );
    }

    private function plannerForCandidates(
        array $candidates
    ): LeaseDeletionRestorationPlanService {
        $planner =
            Mockery::mock(
                LeaseDeletionRestorationPlanService::class
            );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn([
                'eligibility' => [
                    'safe_to_execute' =>
                        true,

                    'blocking_reasons' =>
                        [],
                ],

                'accounting' => [
                    'shared_entries' =>
                        [],

                    'unclassified_entries' =>
                        [],

                    'opening_balance_actions' =>
                        [],

                    'reversal_candidates' =>
                        $candidates,
                ],
            ]);

        return $planner;
    }

    private function candidate(
        JournalEntry $entry
    ): array {
        return [
            'journal_entry_id' =>
                (int) $entry->id,

            'journal_number' =>
                $entry->journal_number,

            'source_type' =>
                $entry->source_type,

            'source_id' =>
                $entry->source_id !== null
                    ? (int) $entry->source_id
                    : null,

            'transaction_type' =>
                $entry->transaction_type,

            'action' =>
                'reverse_full_entry',
        ];
    }

    private function postLeaseJournal(
        Lease $lease,
        string $key
    ): JournalEntry {
        $account =
            TenantFundAccount::query()
                ->firstOrCreate(
                    [
                        'lease_id' =>
                            $lease->id,

                        'type' =>
                            'rent_reserve',
                    ],
                    [
                        'status' =>
                            'active',
                    ]
                );

        $transaction =
            TenantFundTransaction::query()
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
                        now()
                            ->toDateString(),
                ]);

        return app(
            JournalPostingService::class
        )->post(
            [
                'journal_date' =>
                    now()->toDateString(),

                'transaction_type' =>
                    'phase_10d2_test',

                'description' =>
                    'Phase 10D2 Journal test.',

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->id,

                'idempotency_key' =>
                    'phase10d2:'.$key,
            ],
            [
                [
                    'account_code' =>
                        SystemChartOfAccounts::CASH,

                    'debit' =>
                        1000,

                    'credit' =>
                        0,
                ],
                [
                    'account_code' =>
                        SystemChartOfAccounts::
                            RENT_RESERVE_HELD,

                    'debit' =>
                        0,

                    'credit' =>
                        1000,
                ],
            ]
        );
    }

    private function createLease(): Lease
    {
        $tenant = Party::query()->create([
            'type' =>
                'person',

            'name' =>
                'Phase 10D2 Tenant',
        ]);

        $building =
            Building::query()->create([
                'name' =>
                    'Phase 10D2 Building',
            ]);

        $unit =
            Unit::query()->create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Phase 10D2 Unit',
            ]);

        return Lease::query()->create([
            'unit_id' =>
                $unit->id,

            'tenant_id' =>
                $tenant->id,

            'start_date' =>
                '2026-01-01',

            'end_date' =>
                '2026-12-31',

            'status' =>
                'draft',

            'rent_amount' =>
                1000,

            'payment_frequency' =>
                'monthly',

            'due_day' =>
                1,

            'vat_rate' =>
                0,

            'proration_amount' =>
                0,

            'security_deposit_amount' =>
                0,

            'advance_payment_amount' =>
                0,

            'rent_reserve_amount' =>
                0,

            'management_fee_type' =>
                'none',

            'management_fee_value' =>
                0,

            'agent_commission_amount' =>
                0,
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
            'journal_entries',
            'journal_lines',
            'activity_logs',
        ];

        $result = [];

        foreach ($tables as $table) {
            $result[$table] =
                DB::table($table)->count();
        }

        return $result;
    }
}
