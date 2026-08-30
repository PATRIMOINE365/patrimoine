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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialJournalExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_exports_require_authentication(): void
    {
        foreach (
            ['csv', 'xlsx']
            as $format
        ) {
            $this
                ->getJson(
                    "/api/financial-journal/{$format}"
                )
                ->assertUnauthorized();
        }
    }

    public function test_property_manager_and_viewer_cannot_export(): void
    {
        $this->entryWithLines();

        foreach (
            [
                UserRole::PropertyManager,
                UserRole::Viewer,
            ]
            as $role
        ) {
            Sanctum::actingAs(
                $this->user(
                    $role
                )
            );

            foreach (
                ['csv', 'xlsx']
                as $format
            ) {
                $this
                    ->get(
                        "/api/financial-journal/{$format}"
                    )
                    ->assertForbidden();
            }
        }
    }

    public function test_administrator_can_export_csv_and_xlsx(): void
    {
        $this->entryWithLines();

        Sanctum::actingAs(
            $this->administrator()
        );

        $csv =
            $this
                ->get(
                    '/api/financial-journal/csv'
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'text/csv; charset=UTF-8'
                );

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $csv->getContent()
        );

        $this->assertStringContainsString(
            'JRN-2026-000901',
            $csv->getContent()
        );

        $xlsx =
            $this
                ->get(
                    '/api/financial-journal/xlsx'
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );

        $this->assertStringStartsWith(
            'PK',
            $xlsx->getContent()
        );
    }

    /**
     * The Financial Journal has no PDF export, and must not answer as if it had.
     *
     * dompdf holds the whole document in memory and this rendered every
     * matching entry: 276 entries needed 456 MB against the 128 MB the
     * live box allows, and it is superlinear. A one-month filter did not
     * rescue it either - a single month of a twelve-unit portfolio is 168
     * entries. Withdrawn in V1.0.35; CSV and XLSX stream and carry the
     * same columns.
     */
    public function test_the_financial_journal_offers_no_pdf_export(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson('/api/financial-journal/pdf')
            ->assertNotFound();
    }

    public function test_csv_contains_accounting_lines(): void
    {
        $context =
            $this->entryWithLines();

        Sanctum::actingAs(
            $this->administrator()
        );

        $content =
            $this
                ->get(
                    '/api/financial-journal/csv'
                )
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            $context['debit']
                ->account_code_snapshot,
            $content
        );

        $this->assertStringContainsString(
            $context['debit']
                ->account_name_snapshot,
            $content
        );

        $this->assertStringContainsString(
            $context['credit']
                ->account_code_snapshot,
            $content
        );

        $this->assertStringContainsString(
            'Export debit memo',
            $content
        );
    }

    public function test_exports_respect_active_filters(): void
    {
        $target =
            $this->entryWithLines(
                journalNumber:
                    'JRN-2026-000911',

                journalDate:
                    '2026-08-20',

                transactionType:
                    'owner_deposit'
            );

        $this->entryWithLines(
            journalNumber:
                'JRN-2026-000912',

            journalDate:
                '2026-08-19',

            transactionType:
                'owner_payout'
        );

        Sanctum::actingAs(
            $this->administrator()
        );

        $query =
            '?from=2026-08-20'
            .'&to=2026-08-20'
            .'&entry_kind=financial'
            .'&transaction_type=owner_deposit'
            .'&account_id='
            .$target['debit']
                ->accounting_account_id;

        $content =
            $this
                ->get(
                    '/api/financial-journal/csv'
                    .$query
                )
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            'JRN-2026-000911',
            $content
        );

        $this->assertStringNotContainsString(
            'JRN-2026-000912',
            $content
        );
    }

    public function test_export_validation_matches_read_api(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        foreach (
            ['csv', 'xlsx']
            as $format
        ) {
            $this
                ->getJson(
                    "/api/financial-journal/{$format}?from=bad-date"
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'from',
                ]);

            $this
                ->getJson(
                    "/api/financial-journal/{$format}?entry_kind=invalid"
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'entry_kind',
                ]);

            $this
                ->getJson(
                    "/api/financial-journal/{$format}"
                    .'?from=2026-08-20'
                    .'&to=2026-08-19'
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'to',
                ]);
        }

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_export_creates_exactly_one_activity_event(): void
    {
        $this->entryWithLines();

        $user =
            $this->administrator();

        Sanctum::actingAs(
            $user
        );

        foreach (
            ['csv', 'xlsx']
            as $format
        ) {
            ActivityLog::query()
                ->delete();

            $this
                ->get(
                    "/api/financial-journal/{$format}"
                    .'?entry_kind=financial'
                )
                ->assertOk();

            $event =
                ActivityLog::query()
                    ->sole();

            $this->assertSame(
                'report.exported',
                $event->action
            );

            $this->assertSame(
                $user->id,
                $event->user_id
            );

            $this->assertSame(
                'financial_journal',
                $event->entity_type
            );

            $this->assertSame(
                'financial_journal',
                $event->metadata[
                    'report_type'
                ]
            );

            $this->assertSame(
                $format,
                $event->metadata[
                    'format'
                ]
            );

            $this->assertSame(
                'financial',
                $event->metadata[
                    'filters'
                ][
                    'entry_kind'
                ]
            );
        }
    }

    public function test_informational_zero_line_entry_is_not_lost(): void
    {
        JournalEntry::create([
            'journal_number' =>
                'JRN-2026-000950',

            'entry_kind' =>
                JournalEntry::KIND_INFORMATIONAL,

            'journal_date' =>
                '2026-08-20',

            'posted_at' =>
                '2026-08-20 15:00:00',

            'transaction_type' =>
                'lease_deleted',

            'description' =>
                'Historical Lease deletion',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $content =
            $this
                ->get(
                    '/api/financial-journal/csv'
                )
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            'JRN-2026-000950',
            $content
        );

        $this->assertStringContainsString(
            'Historical Lease deletion',
            $content
        );
    }

    private function entryWithLines(
        string $journalNumber =
            'JRN-2026-000901',

        string $journalDate =
            '2026-08-20',

        string $transactionType =
            'owner_deposit',

        int $amount =
            4200
    ): array {
        $entry =
            JournalEntry::create([
                'journal_number' =>
                    $journalNumber,

                'entry_kind' =>
                    JournalEntry::KIND_FINANCIAL,

                'journal_date' =>
                    $journalDate,

                'posted_at' =>
                    $journalDate
                    .' 12:00:00',

                'transaction_type' =>
                    $transactionType,

                'description' =>
                    'Financial Journal export fixture',

                'actor_name_snapshot' =>
                    'Historical Administrator',

                'source_type' =>
                    'App\\Models\\OwnerTransaction',

                'source_id' =>
                    500,
            ]);

        $bank =
            AccountingAccount::query()
                ->where(
                    'code',
                    SystemChartOfAccounts::BANK
                )
                ->firstOrFail();

        $ownerPayable =
            AccountingAccount::query()
                ->where(
                    'code',
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE
                )
                ->firstOrFail();

        $debit =
            JournalLine::create([
                'journal_entry_id' =>
                    $entry->id,

                'accounting_account_id' =>
                    $bank->id,

                'debit_amount' =>
                    $amount,

                'credit_amount' =>
                    0,

                'account_code_snapshot' =>
                    $bank->code,

                'account_name_snapshot' =>
                    $bank->name,

                'account_type_snapshot' =>
                    $bank->type,

                'memo' =>
                    'Export debit memo',
            ]);

        $credit =
            JournalLine::create([
                'journal_entry_id' =>
                    $entry->id,

                'accounting_account_id' =>
                    $ownerPayable->id,

                'debit_amount' =>
                    0,

                'credit_amount' =>
                    $amount,

                'account_code_snapshot' =>
                    $ownerPayable->code,

                'account_name_snapshot' =>
                    $ownerPayable->name,

                'account_type_snapshot' =>
                    $ownerPayable->type,

                'memo' =>
                    'Export credit memo',
            ]);

        return compact(
            'entry',
            'debit',
            'credit'
        );
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
