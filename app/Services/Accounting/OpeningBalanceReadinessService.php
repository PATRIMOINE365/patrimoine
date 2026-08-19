<?php

namespace App\Services\Accounting;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only safety gate for the V1.0.5 accounting opening-balance cutover.
 *
 * This service deliberately performs no accounting mutation.
 *
 * It answers three operational questions:
 *
 * 1. Is a pre-cutover database safe to cut over?
 * 2. Has a cutover already been performed?
 * 3. If cut over, does the opening Financial Journal still reconcile?
 *
 * It is not a posting service and must never invoke the cutover service.
 */
class OpeningBalanceReadinessService
{
    public const STATE_READY_FOR_CUTOVER =
        'ready_for_cutover';

    public const STATE_BLOCKED_PRE_CUTOVER =
        'blocked_pre_cutover';

    public const STATE_CUTOVER_RECONCILED =
        'cutover_reconciled';

    public const STATE_CUTOVER_FAILED_RECONCILIATION =
        'cutover_failed_reconciliation';

    public function __construct(
        private readonly OpeningBalanceDiscoveryService $discovery,
        private readonly OpeningBalanceReconciliationService $reconciliation,
    ) {
    }

    /**
     * Evaluate the current accounting-cutover state without modifying data.
     *
     * @return array<string, mixed>
     */
    public function evaluate(): array
    {
        $before = $this->mutationSnapshot();

        $cutover =
            AccountingCutover::query()
                ->latest('id')
                ->first();

        if ($cutover === null) {
            $result =
                $this->evaluatePreCutover();
        } else {
            $result =
                $this->evaluatePostCutover(
                    $cutover->id
                );
        }

        $after = $this->mutationSnapshot();

        $readOnly =
            $before === $after;

        $result['read_only'] =
            $readOnly;

        $result['mutation_check'] = [
            'before' =>
                $before,

            'after' =>
                $after,

            'unchanged' =>
                $readOnly,
        ];

        if (! $readOnly) {
            $result['passed'] =
                false;

            $result['blockers'][] =
                'Readiness evaluation modified accounting data.';
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluatePreCutover(): array
    {
        try {
            $discovery =
                $this->discovery->discover();

            $sourceReconciled =
                (bool) (
                    $discovery[
                        'source_totals_match_positions'
                    ]
                    ?? data_get(
                        $discovery,
                        'reconciliation.source_totals_match_positions',
                        false
                    )
                );

            $positions =
                $discovery['positions']
                ?? [];

            $positionCount =
                is_countable($positions)
                    ? count($positions)
                    : (int) (
                        $discovery['position_count']
                        ?? 0
                    );

            $journalEntries =
                JournalEntry::query()->count();

            $journalLines =
                JournalLine::query()->count();

            $blockers = [];

            if (! $sourceReconciled) {
                $blockers[] =
                    'Operational source totals do not reconcile to the discovered opening positions.';
            }

            if ($journalEntries !== 0) {
                $blockers[] =
                    'Financial Journal is not empty before opening-balance cutover.';
            }

            if ($journalLines !== 0) {
                $blockers[] =
                    'Financial Journal lines are not empty before opening-balance cutover.';
            }

            $ready =
                $blockers === [];

            return [
                'state' =>
                    $ready
                        ? self::STATE_READY_FOR_CUTOVER
                        : self::STATE_BLOCKED_PRE_CUTOVER,

                'passed' =>
                    $ready,

                'cutover_initialized' =>
                    false,

                'ready_for_cutover' =>
                    $ready,

                'source_reconciled' =>
                    $sourceReconciled,

                'position_count' =>
                    $positionCount,

                'journal_entries' =>
                    $journalEntries,

                'journal_lines' =>
                    $journalLines,

                'post_cutover_reconciled' =>
                    null,

                'blockers' =>
                    $blockers,
            ];
        } catch (Throwable $exception) {
            return [
                'state' =>
                    self::STATE_BLOCKED_PRE_CUTOVER,

                'passed' =>
                    false,

                'cutover_initialized' =>
                    false,

                'ready_for_cutover' =>
                    false,

                'source_reconciled' =>
                    false,

                'position_count' =>
                    null,

                'journal_entries' =>
                    JournalEntry::query()->count(),

                'journal_lines' =>
                    JournalLine::query()->count(),

                'post_cutover_reconciled' =>
                    null,

                'blockers' => [
                    'Opening-balance discovery failed: '
                    .$exception->getMessage(),
                ],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluatePostCutover(
        int $cutoverId
    ): array {
        try {
            $reconciliation =
                $this->reconciliation->reconcile();

            $reconciled =
                (bool) (
                    $reconciliation['reconciled']
                    ?? false
                );

            $sourceReconciled =
                (bool) (
                    data_get(
                        $reconciliation,
                        'operational_sources.reconciled',
                        data_get(
                            $reconciliation,
                            'discovery.source_totals_match_positions',
                            false
                        )
                    )
                );

            $positionCount =
                (int) (
                    data_get(
                        $reconciliation,
                        'opening_positions.count',
                        data_get(
                            $reconciliation,
                            'discovery.position_count',
                            0
                        )
                    )
                );

            $openingEntries =
                (int) data_get(
                    $reconciliation,
                    'opening_journal.entries',
                    0
                );

            $positionCountMatches =
                (bool) data_get(
                    $reconciliation,
                    'opening_journal.position_count_matches',
                    false
                );

            $accountTotalsMatch =
                (bool) data_get(
                    $reconciliation,
                    'opening_journal.account_totals_match',
                    false
                );

            $allEntriesBalanced =
                (bool) data_get(
                    $reconciliation,
                    'opening_journal.all_entries_balanced',
                    false
                );

            $reconciliationReadOnly =
                (bool) data_get(
                    $reconciliation,
                    'journal_mutation_check.unchanged',
                    false
                );

            $blockers = [];

            if (! $reconciled) {
                $blockers[] =
                    'Post-cutover opening balance reconciliation failed.';
            }

            if (! $positionCountMatches) {
                $blockers[] =
                    'Opening Journal entry count does not match the opening position count.';
            }

            if (! $accountTotalsMatch) {
                $blockers[] =
                    'Opening Journal account totals do not match operational opening positions.';
            }

            if (! $allEntriesBalanced) {
                $blockers[] =
                    'One or more opening Journal entries are unbalanced.';
            }

            if (! $reconciliationReadOnly) {
                $blockers[] =
                    'Reconciliation modified Financial Journal data.';
            }

            $passed =
                $reconciled
                && $blockers === [];

            return [
                'state' =>
                    $passed
                        ? self::STATE_CUTOVER_RECONCILED
                        : self::STATE_CUTOVER_FAILED_RECONCILIATION,

                'passed' =>
                    $passed,

                'cutover_initialized' =>
                    true,

                'cutover_id' =>
                    $cutoverId,

                'ready_for_cutover' =>
                    false,

                'source_reconciled' =>
                    $sourceReconciled,

                'position_count' =>
                    $positionCount,

                'opening_journal_entries' =>
                    $openingEntries,

                'position_count_matches' =>
                    $positionCountMatches,

                'account_totals_match' =>
                    $accountTotalsMatch,

                'all_entries_balanced' =>
                    $allEntriesBalanced,

                'reconciliation_read_only' =>
                    $reconciliationReadOnly,

                'post_cutover_reconciled' =>
                    $reconciled,

                'blockers' =>
                    $blockers,
            ];
        } catch (Throwable $exception) {
            return [
                'state' =>
                    self::STATE_CUTOVER_FAILED_RECONCILIATION,

                'passed' =>
                    false,

                'cutover_initialized' =>
                    true,

                'cutover_id' =>
                    $cutoverId,

                'ready_for_cutover' =>
                    false,

                'source_reconciled' =>
                    false,

                'post_cutover_reconciled' =>
                    false,

                'blockers' => [
                    'Post-cutover reconciliation failed: '
                    .$exception->getMessage(),
                ],
            ];
        }
    }

    /**
     * Snapshot only tables this readiness gate must never mutate.
     *
     * @return array<string, int>
     */
    private function mutationSnapshot(): array
    {
        return [
            'accounting_cutovers' =>
                DB::table(
                    'accounting_cutovers'
                )->count(),

            'journal_entries' =>
                DB::table(
                    'journal_entries'
                )->count(),

            'journal_lines' =>
                DB::table(
                    'journal_lines'
                )->count(),
        ];
    }
}
