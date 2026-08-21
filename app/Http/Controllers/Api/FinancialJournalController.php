<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\FinancialJournalQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administrator-only read API for Patrimoine's immutable Financial Journal.
 *
 * This controller deliberately provides only index and show endpoints.
 * Accounting corrections continue to use controlled accounting workflows;
 * this API cannot create, edit, reverse or delete Journal history.
 */
class FinancialJournalController extends Controller
{
    public function index(
        Request $request,
        FinancialJournalQueryService $journalQuery
    ): JsonResponse {
        $validated =
            $journalQuery
                ->validatedFilters(
                    $request
                );

        $entries =
            $journalQuery
                ->query(
                    $validated
                )
                ->select([
                    'id',
                    'journal_number',
                    'entry_kind',
                    'reversal_of_id',
                    'reversed_by_id',
                    'journal_date',
                    'posted_at',
                    'transaction_type',
                    'description',
                    'source_type',
                    'source_id',
                    'actor_user_id',
                    'actor_name_snapshot',
                ])
                ->withSum(
                    'lines as debit_total',
                    'debit_amount'
                )
                ->withSum(
                    'lines as credit_total',
                    'credit_amount'
                )
                ->paginate(
                    perPage:
                        (int) (
                            $validated['per_page']
                            ?? 25
                        )
                );

        /*
         * Normalize aggregate totals as integers instead of leaking
         * database-driver-specific numeric strings into the browser API.
         */
        $entries->through(
            function (
                JournalEntry $entry
            ): array {
                return [
                    'id' =>
                        $entry->id,

                    'journal_number' =>
                        $entry->journal_number,

                    'entry_kind' =>
                        $entry->entry_kind,

                    'reversal_of_id' =>
                        $entry->reversal_of_id,

                    'reversed_by_id' =>
                        $entry->reversed_by_id,

                    'is_reversed' =>
                        $entry->isReversed(),

                    'journal_date' =>
                        $entry->journal_date,

                    'posted_at' =>
                        $entry->posted_at,

                    'transaction_type' =>
                        $entry->transaction_type,

                    'description' =>
                        $entry->description,

                    'source_type' =>
                        $entry->source_type,

                    'source_id' =>
                        $entry->source_id,

                    'actor_user_id' =>
                        $entry->actor_user_id,

                    'actor_name_snapshot' =>
                        $entry->actor_name_snapshot,

                    'debit_total' =>
                        (int) (
                            $entry->debit_total
                            ?? 0
                        ),

                    'credit_total' =>
                        (int) (
                            $entry->credit_total
                            ?? 0
                        ),
                ];
            }
        );

        return response()->json(
            $entries
        );
    }

    /**
     * Read-only browser filter options derived from immutable Journal data.
     */
    public function filterOptions(): JsonResponse
    {
        $transactionTypes =
            JournalEntry::query()
                ->whereNotNull(
                    'transaction_type'
                )
                ->where(
                    'transaction_type',
                    '!=',
                    ''
                )
                ->distinct()
                ->orderBy(
                    'transaction_type'
                )
                ->pluck(
                    'transaction_type'
                )
                ->values();

        $accounts =
            JournalLine::query()
                ->select([
                    'accounting_account_id',
                    'account_code_snapshot',
                    'account_name_snapshot',
                ])
                ->distinct()
                ->orderBy(
                    'account_code_snapshot'
                )
                ->orderBy(
                    'account_name_snapshot'
                )
                ->get()
                ->map(
                    static fn (
                        JournalLine $line
                    ): array => [
                        'id' =>
                            $line->accounting_account_id,

                        'code' =>
                            $line->account_code_snapshot,

                        'name' =>
                            $line->account_name_snapshot,
                    ]
                )
                ->unique(
                    static fn (
                        array $account
                    ): string =>
                        (string) $account['id']
                )
                ->values();

        return response()->json([
            'entry_kinds' => [
                JournalEntry::KIND_FINANCIAL,
                JournalEntry::KIND_REVERSAL,
                JournalEntry::KIND_INFORMATIONAL,
            ],

            'transaction_types' =>
                $transactionTypes,

            'accounts' =>
                $accounts,
        ]);
    }

    public function show(
        JournalEntry $journalEntry
    ): JsonResponse {
        $journalEntry->load([
            'lines',
            'reversedEntry:id,journal_number,entry_kind,journal_date,transaction_type,description',
            'reversalEntry:id,journal_number,entry_kind,journal_date,transaction_type,description,reversal_reason',
        ]);

        $data =
            $journalEntry->toArray();

        $data['debit_total'] =
            $journalEntry->debitTotal();

        $data['credit_total'] =
            $journalEntry->creditTotal();

        $data['is_balanced'] =
            $journalEntry->isBalanced();

        $data['is_reversed'] =
            $journalEntry->isReversed();

        /*
         * The relationships above contain only concise immutable Journal
         * references; no deleted operational source needs to exist.
         */
        $data['reversal_of'] =
            $journalEntry
                ->reversedEntry
                ? [
                    'id' =>
                        $journalEntry
                            ->reversedEntry
                            ->id,

                    'journal_number' =>
                        $journalEntry
                            ->reversedEntry
                            ->journal_number,

                    'entry_kind' =>
                        $journalEntry
                            ->reversedEntry
                            ->entry_kind,

                    'journal_date' =>
                        $journalEntry
                            ->reversedEntry
                            ->journal_date,

                    'transaction_type' =>
                        $journalEntry
                            ->reversedEntry
                            ->transaction_type,

                    'description' =>
                        $journalEntry
                            ->reversedEntry
                            ->description,
                ]
                : null;

        $data['reversed_by'] =
            $journalEntry
                ->reversalEntry
                ? [
                    'id' =>
                        $journalEntry
                            ->reversalEntry
                            ->id,

                    'journal_number' =>
                        $journalEntry
                            ->reversalEntry
                            ->journal_number,

                    'entry_kind' =>
                        $journalEntry
                            ->reversalEntry
                            ->entry_kind,

                    'journal_date' =>
                        $journalEntry
                            ->reversalEntry
                            ->journal_date,

                    'transaction_type' =>
                        $journalEntry
                            ->reversalEntry
                            ->transaction_type,

                    'description' =>
                        $journalEntry
                            ->reversalEntry
                            ->description,

                    'reversal_reason' =>
                        $journalEntry
                            ->reversalEntry
                            ->reversal_reason,
                ]
                : null;

        return response()->json(
            $data
        );
    }
}
