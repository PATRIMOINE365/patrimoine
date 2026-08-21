<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Creates immutable accounting reversals.
 *
 * A reversal never modifies the financial contents of the original posting.
 * Instead, Patrimoine creates a new balanced Journal transaction containing
 * the exact opposite debit/credit lines and permanently links the two.
 */
class JournalReversalService
{
    public function __construct(
        private readonly JournalPostingService $postingService
    ) {
    }

    public function reverse(
        JournalEntry $original,
        string $reason,
        ?int $actorUserId = null
    ): JournalEntry {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $original,
            $reason,
            $actorUserId
        ): JournalEntry {
            $original = JournalEntry::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($original->getKey());

            if ($original->isInformational()) {
                throw ValidationException::withMessages([
                    'journal_entry' =>
                        'Informational Journal entries cannot be financially reversed.',
                ]);
            }

            if ($original->isReversal()) {
                throw ValidationException::withMessages([
                    'journal_entry' =>
                        'A reversal Journal entry cannot itself be reversed.',
                ]);
            }

            if ($original->isReversed()) {
                throw ValidationException::withMessages([
                    'journal_entry' =>
                        'This Journal entry has already been reversed.',
                ]);
            }

            if ($original->lines->isEmpty()) {
                throw new RuntimeException(
                    'Financial Journal entry has no Journal lines.'
                );
            }

            /*
             * Exact opposite of every original accounting line:
             *
             * original debit  -> reversal credit
             * original credit -> reversal debit
             */
            $lines = $original->lines
                ->map(function ($line): array {
                    return [
                        'account_code' =>
                            $line->account_code_snapshot,

                        'debit' =>
                            (int) $line->credit_amount,

                        'credit' =>
                            (int) $line->debit_amount,

                        'memo' =>
                            $line->memo,

                        'snapshot' => [
                            'reversal_of_journal_line_id' =>
                                $line->id,
                        ],
                    ];
                })
                ->values()
                ->all();

            $reversal = $this->postingService->post(
                [
                    'journal_date' => now()->toDateString(),

                    'transaction_type' =>
                        'journal_reversal',

                    'description' => sprintf(
                        'Reversal of %s: %s',
                        $original->journal_number,
                        $reason
                    ),

                    'source_type' =>
                        JournalEntry::class,

                    'source_id' =>
                        $original->id,

                    'actor_user_id' =>
                        $actorUserId,

                    'snapshot' => [
                        'reversal_of_journal_number' =>
                            $original->journal_number,

                        'reversal_of_transaction_type' =>
                            $original->transaction_type,
                    ],

                    'metadata' => [
                        'reversal_reason' =>
                            $reason,
                    ],
                ],
                $lines
            );

            /*
             * Financial Journal records are immutable through their models.
             *
             * These direct updates add permanent relationship metadata only.
             * They do not alter the financial content of either posting.
             */
            DB::table('journal_entries')
                ->where('id', $reversal->id)
                ->update([
                    'entry_kind' =>
                        JournalEntry::KIND_REVERSAL,

                    'reversal_of_id' =>
                        $original->id,

                    'reversal_reason' =>
                        $reason,

                    'updated_at' =>
                        now(),
                ]);

            DB::table('journal_entries')
                ->where('id', $original->id)
                ->update([
                    'reversed_by_id' =>
                        $reversal->id,

                    'updated_at' =>
                        now(),
                ]);

            return JournalEntry::query()
                ->with('lines')
                ->findOrFail($reversal->id);
        });
    }
}
