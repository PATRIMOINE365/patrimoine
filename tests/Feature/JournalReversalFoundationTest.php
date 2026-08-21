<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\JournalInformationalEventService;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JournalReversalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemChartOfAccounts::class)->install();
    }

    public function test_journal_schema_supports_reversal_linkage_and_entry_kind(): void
    {
        $this->assertTrue(
            Schema::hasColumns('journal_entries', [
                'entry_kind',
                'reversal_of_id',
                'reversed_by_id',
                'reversal_reason',
            ])
        );
    }

    public function test_real_financial_entry_can_be_reversed(): void
    {
        $original = $this->postOriginal();

        $reversal = app(
            JournalReversalService::class
        )->reverse(
            $original,
            'Payment was entered against the wrong transaction.'
        );

        $original->refresh();

        $this->assertSame(
            JournalEntry::KIND_REVERSAL,
            $reversal->entry_kind
        );

        $this->assertSame(
            $original->id,
            $reversal->reversal_of_id
        );

        $this->assertSame(
            $reversal->id,
            $original->reversed_by_id
        );

        $this->assertSame(
            'Payment was entered against the wrong transaction.',
            $reversal->reversal_reason
        );

        $this->assertTrue($original->isReversed());
        $this->assertTrue($reversal->isReversal());

        $this->assertSame(
            $original->debitTotal(),
            $reversal->creditTotal()
        );

        $this->assertSame(
            $original->creditTotal(),
            $reversal->debitTotal()
        );

        $this->assertTrue($reversal->isBalanced());

        $this->assertDatabaseCount(
            'journal_entries',
            2
        );

        $this->assertDatabaseCount(
            'journal_lines',
            4
        );
    }

    public function test_reversal_lines_are_exact_opposites_of_original_lines(): void
    {
        $original = $this->postOriginal();

        $reversal = app(
            JournalReversalService::class
        )->reverse(
            $original,
            'Correction required.'
        );

        $originalCash = $original->lines
            ->firstWhere(
                'account_code_snapshot',
                SystemChartOfAccounts::CASH
            );

        $reversalCash = $reversal->lines
            ->firstWhere(
                'account_code_snapshot',
                SystemChartOfAccounts::CASH
            );

        $this->assertSame(
            2500,
            $originalCash->debit_amount
        );

        $this->assertSame(
            0,
            $originalCash->credit_amount
        );

        $this->assertSame(
            0,
            $reversalCash->debit_amount
        );

        $this->assertSame(
            2500,
            $reversalCash->credit_amount
        );
    }

    public function test_reversal_requires_mandatory_reason(): void
    {
        $original = $this->postOriginal();

        $this->expectException(
            ValidationException::class
        );

        app(JournalReversalService::class)
            ->reverse(
                $original,
                '   '
            );
    }

    public function test_original_entry_cannot_be_reversed_twice(): void
    {
        $original = $this->postOriginal();

        $service = app(
            JournalReversalService::class
        );

        $service->reverse(
            $original,
            'First reversal.'
        );

        $this->expectException(
            ValidationException::class
        );

        $service->reverse(
            $original,
            'Second reversal.'
        );
    }

    public function test_reversal_entry_cannot_itself_be_reversed(): void
    {
        $original = $this->postOriginal();

        $service = app(
            JournalReversalService::class
        );

        $reversal = $service->reverse(
            $original,
            'Original reversal.'
        );

        $this->expectException(
            ValidationException::class
        );

        $service->reverse(
            $reversal,
            'Attempt to reverse reversal.'
        );
    }

    public function test_informational_event_is_zero_value_and_has_no_lines(): void
    {
        $entry = app(
            JournalInformationalEventService::class
        )->record(
            transactionType: 'lease_deleted',
            description: 'Lease was destructively deleted.',
            sourceType: 'lease',
            sourceId: 123,
            snapshot: [
                'lease_reference' => 'LEASE-123',
            ]
        );

        $this->assertTrue(
            $entry->isInformational()
        );

        $this->assertSame(
            JournalEntry::KIND_INFORMATIONAL,
            $entry->entry_kind
        );

        $this->assertSame(
            0,
            $entry->lines()->count()
        );

        $this->assertSame(
            0,
            $entry->debitTotal()
        );

        $this->assertSame(
            0,
            $entry->creditTotal()
        );

        $this->assertDatabaseHas(
            'journal_entries',
            [
                'id' => $entry->id,
                'transaction_type' =>
                    'lease_deleted',

                'source_type' =>
                    'lease',

                'source_id' =>
                    123,
            ]
        );
    }

    public function test_informational_event_uses_same_annual_sequence(): void
    {
        $financial = $this->postOriginal();

        $informational = app(
            JournalInformationalEventService::class
        )->record(
            transactionType: 'test_information',
            description: 'Information only.',
            journalDate: '2026-08-19'
        );

        $this->assertSame(
            'JRN-2026-000001',
            $financial->journal_number
        );

        $this->assertSame(
            'JRN-2026-000002',
            $informational->journal_number
        );
    }

    public function test_informational_event_freezes_actor_identity(): void
    {
        $user = User::factory()->create([
            'name' => 'Journal Actor',
        ]);

        $entry = app(
            JournalInformationalEventService::class
        )->record(
            transactionType: 'test_information',
            description: 'Information only.',
            actorUserId: $user->id
        );

        $this->assertSame(
            'Journal Actor',
            $entry->actor_name_snapshot
        );

        $user->update([
            'name' => 'Renamed Actor',
        ]);

        $entry->refresh();

        $this->assertSame(
            'Journal Actor',
            $entry->actor_name_snapshot
        );
    }

    public function test_informational_event_requires_transaction_type(): void
    {
        $this->expectException(
            ValidationException::class
        );

        app(
            JournalInformationalEventService::class
        )->record(
            transactionType: '',
            description: 'Description.'
        );
    }

    public function test_informational_event_requires_description(): void
    {
        $this->expectException(
            ValidationException::class
        );

        app(
            JournalInformationalEventService::class
        )->record(
            transactionType: 'test_information',
            description: ''
        );
    }

    private function postOriginal(): JournalEntry
    {
        return app(
            JournalPostingService::class
        )->post(
            [
                'journal_date' =>
                    '2026-08-19',

                'transaction_type' =>
                    'reversal_test',

                'description' =>
                    'Original financial posting.',
            ],
            [
                [
                    'account_code' =>
                        SystemChartOfAccounts::CASH,

                    'debit' =>
                        2500,

                    'credit' =>
                        0,
                ],
                [
                    'account_code' =>
                        SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                    'debit' =>
                        0,

                    'credit' =>
                        2500,
                ],
            ]
        );
    }
}
