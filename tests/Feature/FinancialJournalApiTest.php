<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AccountingAccount;
use App\Models\ActivityLog;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialJournalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_financial_journal_requires_authentication(): void
    {
        $this
            ->getJson(
                '/api/financial-journal'
            )
            ->assertUnauthorized();
    }

    public function test_only_administrator_can_access_financial_journal(): void
    {
        $entry =
            $this->entry();

        foreach (
            [
                UserRole::PropertyManager,
                UserRole::Viewer,
            ] as $role
        ) {
            Sanctum::actingAs(
                $this->user(
                    $role
                )
            );

            $this
                ->getJson(
                    '/api/financial-journal'
                )
                ->assertForbidden();

            $this
                ->getJson(
                    "/api/financial-journal/{$entry->id}"
                )
                ->assertForbidden();
        }

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/financial-journal'
            )
            ->assertOk();

        $this
            ->getJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertOk();
    }

    public function test_index_is_paginated_and_newest_first(): void
    {
        $oldest =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000001',

                'journal_date' =>
                    '2026-08-18',

                'posted_at' =>
                    '2026-08-18 09:00:00',
            ]);

        $middle =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000002',

                'journal_date' =>
                    '2026-08-19',

                'posted_at' =>
                    '2026-08-19 09:00:00',
            ]);

        $newest =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000003',

                'journal_date' =>
                    '2026-08-20',

                'posted_at' =>
                    '2026-08-20 09:00:00',
            ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this
                ->getJson(
                    '/api/financial-journal?per_page=2'
                )
                ->assertOk()
                ->assertJsonPath(
                    'total',
                    3
                )
                ->assertJsonPath(
                    'per_page',
                    2
                )
                ->assertJsonPath(
                    'data.0.id',
                    $newest->id
                )
                ->assertJsonPath(
                    'data.1.id',
                    $middle->id
                );

        $this->assertNotSame(
            $oldest->id,
            $response->json(
                'data.0.id'
            )
        );
    }

    public function test_index_defaults_to_twenty_five_per_page(): void
    {
        $this->entry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/financial-journal'
            )
            ->assertOk()
            ->assertJsonPath(
                'per_page',
                25
            );
    }

    public function test_header_filters_can_be_combined(): void
    {
        $actor =
            $this->user(
                UserRole::Administrator
            );

        $matching =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000101',

                'entry_kind' =>
                    JournalEntry::KIND_FINANCIAL,

                'journal_date' =>
                    '2026-08-20',

                'transaction_type' =>
                    'owner_deposit',

                'actor_user_id' =>
                    $actor->id,

                'actor_name_snapshot' =>
                    'Frozen Administrator',

                'source_type' =>
                    'App\\Models\\OwnerTransaction',

                'source_id' =>
                    44,
            ]);

        $this->entry([
            'journal_number' =>
                'JRN-2026-000102',

            'entry_kind' =>
                JournalEntry::KIND_INFORMATIONAL,

            'journal_date' =>
                '2026-08-19',

            'transaction_type' =>
                'lease_deleted',

            'source_type' =>
                'App\\Models\\Lease',

            'source_id' =>
                99,
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $url =
            '/api/financial-journal'
            .'?from=2026-08-20'
            .'&to=2026-08-20'
            .'&journal_number=JRN-2026-000101'
            .'&entry_kind=financial'
            .'&transaction_type=owner_deposit'
            ."&actor_user_id={$actor->id}"
            .'&source_type='
            .urlencode(
                'App\\Models\\OwnerTransaction'
            )
            .'&source_id=44';

        $this
            ->getJson(
                $url
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );
    }

    public function test_account_filter_uses_journal_lines(): void
    {
        $bank =
            $this->account(
                SystemChartOfAccounts::BANK
            );

        $other =
            AccountingAccount::query()
                ->where(
                    'id',
                    '!=',
                    $bank->id
                )
                ->firstOrFail();

        $matching =
            $this->entry();

        $this->line(
            $matching,
            $bank,
            debit: 5000
        );

        $nonMatching =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000202',
            ]);

        $this->line(
            $nonMatching,
            $other,
            debit: 3000
        );

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/financial-journal'
                ."?account_id={$bank->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );
    }

    public function test_free_text_search_uses_header_and_frozen_context(): void
    {
        $entry =
            $this->entry([
                'description' =>
                    'Owner deposit for Needle Estate',

                'actor_name_snapshot' =>
                    'Historical Needle Administrator',

                'snapshot' => [
                    'reference' =>
                        'NEEDLE-SNAPSHOT-7788',
                ],

                'metadata' => [
                    'batch' =>
                        'NEEDLE-METADATA-9911',
                ],
            ]);

        $account =
            $this->account(
                SystemChartOfAccounts::BANK
            );

        $this->line(
            $entry,
            $account,
            debit: 1000,
            memo: 'Needle line memo',
            snapshot: [
                'context' =>
                    'NEEDLE-LINE-SNAPSHOT',
            ]
        );

        Sanctum::actingAs(
            $this->administrator()
        );

        foreach (
            [
                'Needle Estate',
                'Historical Needle Administrator',
                'NEEDLE-SNAPSHOT-7788',
                'NEEDLE-METADATA-9911',
                $account->code,
                $account->name,
                'Needle line memo',
                'NEEDLE-LINE-SNAPSHOT',
            ] as $search
        ) {
            $this
                ->getJson(
                    '/api/financial-journal?search='
                    .urlencode(
                        $search
                    )
                )
                ->assertOk()
                ->assertJsonPath(
                    'total',
                    1
                )
                ->assertJsonPath(
                    'data.0.id',
                    $entry->id
                );
        }
    }

    public function test_index_is_lightweight_and_exposes_totals(): void
    {
        $entry =
            $this->entry([
                'snapshot' => [
                    'large' =>
                        'hidden from list',
                ],

                'metadata' => [
                    'internal' =>
                        'hidden from list',
                ],
            ]);

        $bank =
            $this->account(
                SystemChartOfAccounts::BANK
            );

        $payable =
            $this->account(
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE
            );

        $this->line(
            $entry,
            $bank,
            debit: 4200
        );

        $this->line(
            $entry,
            $payable,
            credit: 4200
        );

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this
                ->getJson(
                    '/api/financial-journal'
                )
                ->assertOk()
                ->assertJsonPath(
                    'data.0.debit_total',
                    4200
                )
                ->assertJsonPath(
                    'data.0.credit_total',
                    4200
                );

        $row =
            $response->json(
                'data.0'
            );

        $this->assertArrayNotHasKey(
            'snapshot',
            $row
        );

        $this->assertArrayNotHasKey(
            'metadata',
            $row
        );

        $this->assertArrayNotHasKey(
            'lines',
            $row
        );
    }

    public function test_detail_returns_frozen_lines_and_totals(): void
    {
        $entry =
            $this->entry([
                'snapshot' => [
                    'tenant_name' =>
                        'Frozen Tenant',
                ],

                'metadata' => [
                    'posting_version' =>
                        1,
                ],
            ]);

        $bank =
            $this->account(
                SystemChartOfAccounts::BANK
            );

        $payable =
            $this->account(
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE
            );

        $debit =
            $this->line(
                $entry,
                $bank,
                debit: 7500,
                memo: 'Frozen debit memo'
            );

        $credit =
            $this->line(
                $entry,
                $payable,
                credit: 7500,
                memo: 'Frozen credit memo'
            );

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'snapshot.tenant_name',
                'Frozen Tenant'
            )
            ->assertJsonPath(
                'metadata.posting_version',
                1
            )
            ->assertJsonPath(
                'debit_total',
                7500
            )
            ->assertJsonPath(
                'credit_total',
                7500
            )
            ->assertJsonPath(
                'is_balanced',
                true
            )
            ->assertJsonPath(
                'lines.0.id',
                $debit->id
            )
            ->assertJsonPath(
                'lines.0.account_code_snapshot',
                $bank->code
            )
            ->assertJsonPath(
                'lines.0.account_name_snapshot',
                $bank->name
            )
            ->assertJsonPath(
                'lines.1.id',
                $credit->id
            );
    }

    public function test_detail_does_not_require_operational_source_to_exist(): void
    {
        $entry =
            $this->entry([
                'source_type' =>
                    'App\\Models\\Lease',

                'source_id' =>
                    999999,

                'snapshot' => [
                    'lease_name' =>
                        'Deleted Historical Lease',
                ],
            ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'source_id',
                999999
            )
            ->assertJsonPath(
                'snapshot.lease_name',
                'Deleted Historical Lease'
            );
    }

    public function test_reversal_context_is_exposed(): void
    {
        $original =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000501',
            ]);

        $reversal =
            $this->entry([
                'journal_number' =>
                    'JRN-2026-000502',

                'entry_kind' =>
                    JournalEntry::KIND_REVERSAL,

                'reversal_of_id' =>
                    $original->id,

                'reversal_reason' =>
                    'Correct mistaken posting.',
            ]);

        /*
         * JournalEntry immutability is authoritative application behavior.
         * The fixture uses the query builder only to construct the already-
         * posted reciprocal reversal linkage that JournalReversalService
         * normally persists during posting.
         */
        DB::table(
            'journal_entries'
        )
            ->where(
                'id',
                $original->id
            )
            ->update([
                'reversed_by_id' =>
                    $reversal->id,
            ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                "/api/financial-journal/{$original->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'is_reversed',
                true
            )
            ->assertJsonPath(
                'reversed_by.id',
                $reversal->id
            )
            ->assertJsonPath(
                'reversed_by.journal_number',
                'JRN-2026-000502'
            )
            ->assertJsonPath(
                'reversed_by.reversal_reason',
                'Correct mistaken posting.'
            );

        $this
            ->getJson(
                "/api/financial-journal/{$reversal->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'reversal_of.id',
                $original->id
            )
            ->assertJsonPath(
                'reversal_of.journal_number',
                'JRN-2026-000501'
            );
    }

    public function test_informational_entry_supports_zero_totals(): void
    {
        $entry =
            $this->entry([
                'entry_kind' =>
                    JournalEntry::KIND_INFORMATIONAL,

                'transaction_type' =>
                    'lease_deleted',
            ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'debit_total',
                0
            )
            ->assertJsonPath(
                'credit_total',
                0
            )
            ->assertJsonPath(
                'is_balanced',
                true
            );
    }

    public function test_filter_validation_is_strict(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/financial-journal?from=bad-date'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'from',
            ]);

        $this
            ->getJson(
                '/api/financial-journal'
                .'?from=2026-08-20'
                .'&to=2026-08-19'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);

        $this
            ->getJson(
                '/api/financial-journal'
                .'?entry_kind=not-valid'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'entry_kind',
            ]);

        $this
            ->getJson(
                '/api/financial-journal'
                .'?per_page=101'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ]);
    }

    public function test_financial_journal_has_no_mutation_api(): void
    {
        $entry =
            $this->entry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->postJson(
                '/api/financial-journal',
                []
            )
            ->assertMethodNotAllowed();

        $this
            ->patchJson(
                "/api/financial-journal/{$entry->id}",
                [
                    'description' =>
                        'tampered',
                ]
            )
            ->assertMethodNotAllowed();

        $this
            ->deleteJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas(
            'journal_entries',
            [
                'id' =>
                    $entry->id,

                'description' =>
                    $entry->description,
            ]
        );
    }

    public function test_passive_financial_journal_reads_do_not_create_activity_log_events(): void
    {
        $entry =
            $this->entry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/financial-journal'
            )
            ->assertOk();

        $this
            ->getJson(
                "/api/financial-journal/{$entry->id}"
            )
            ->assertOk();

        $this->assertSame(
            0,
            ActivityLog::count()
        );
    }

    private function entry(
        array $values = []
    ): JournalEntry {
        return JournalEntry::create(
            array_merge(
                [
                    'journal_number' =>
                        'JRN-2026-000100',

                    'entry_kind' =>
                        JournalEntry::KIND_FINANCIAL,

                    'journal_date' =>
                        '2026-08-20',

                    'posted_at' =>
                        '2026-08-20 12:00:00',

                    'transaction_type' =>
                        'test_financial_event',

                    'description' =>
                        'Financial Journal API test',

                    'source_type' =>
                        'App\\Models\\TestSource',

                    'source_id' =>
                        1,

                    'actor_name_snapshot' =>
                        'Historical Administrator',

                    'snapshot' =>
                        null,

                    'metadata' =>
                        null,
                ],
                $values
            )
        );
    }

    private function line(
        JournalEntry $entry,
        AccountingAccount $account,
        int $debit = 0,
        int $credit = 0,
        ?string $memo = null,
        ?array $snapshot = null
    ): JournalLine {
        return JournalLine::create([
            'journal_entry_id' =>
                $entry->id,

            'accounting_account_id' =>
                $account->id,

            'debit_amount' =>
                $debit,

            'credit_amount' =>
                $credit,

            'account_code_snapshot' =>
                $account->code,

            'account_name_snapshot' =>
                $account->name,

            'account_type_snapshot' =>
                $account->type,

            'memo' =>
                $memo,

            'snapshot' =>
                $snapshot,
        ]);
    }

    private function account(
        string $code
    ): AccountingAccount {
        return AccountingAccount::query()
            ->where(
                'code',
                $code
            )
            ->firstOrFail();
    }

    private function administrator(): User
    {
        return $this->user(
            UserRole::Administrator
        );
    }

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' =>
                $role,

            'is_active' =>
                true,

            'email_verified_at' =>
                now(),
        ]);
    }
}
