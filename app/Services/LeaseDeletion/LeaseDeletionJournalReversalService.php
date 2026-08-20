<?php

namespace App\Services\LeaseDeletion;

use App\Models\JournalEntry;
use App\Models\Lease;
use App\Services\Accounting\JournalReversalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Phase 10D2
 *
 * Executes only the Financial Journal reversal portion of destructive
 * Lease deletion.
 *
 * IMPORTANT:
 * - This service does NOT delete the Lease.
 * - This service does NOT delete operational financial records.
 * - This service does NOT delete Journal or Activity Log history.
 * - This service does NOT create the final informational deletion event.
 * - This service does NOT create the final Activity Log event.
 *
 * The future destructive Lease-deletion orchestrator will call this service
 * inside the same larger atomic transaction as operational deletion.
 */
class LeaseDeletionJournalReversalService
{
    public function __construct(
        private readonly LeaseDeletionRestorationPlanService $planner,
        private readonly JournalReversalService $reversalService,
    ) {
    }

    /**
     * Reverse all currently-authorized Lease-specific Journal effects.
     *
     * @return array<string, mixed>
     */
    public function execute(
        Lease $lease,
        string $reason,
        ?int $actorUserId = null
    ): array {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' =>
                    'A Lease deletion reversal reason is required.',
            ]);
        }

        return DB::transaction(
            function () use (
                $lease,
                $reason,
                $actorUserId
            ): array {
                /*
                 * Rebuild the authoritative plan inside the execution
                 * transaction. Caller-supplied candidate IDs are never
                 * trusted.
                 */
                $plan =
                    $this->planner->plan(
                        $lease
                    );

                $this->assertPlanExecutable(
                    $plan
                );

                $reversed = [];
                $alreadyNeutralized = [];

                foreach (
                    $plan['accounting']['reversal_candidates']
                        ?? []
                    as $candidate
                ) {
                    if (
                        ($candidate['action'] ?? null)
                        !== 'reverse_full_entry'
                    ) {
                        throw new RuntimeException(
                            'Unsupported Lease deletion Journal reversal action.'
                        );
                    }

                    $journalEntryId =
                        (int) (
                            $candidate[
                                'journal_entry_id'
                            ]
                            ?? 0
                        );

                    if ($journalEntryId <= 0) {
                        throw new RuntimeException(
                            'Lease deletion Journal reversal candidate has no valid Journal entry ID.'
                        );
                    }

                    /*
                     * Reload under lock immediately before execution.
                     *
                     * This protects against changes between planning and the
                     * actual reversal step.
                     */
                    $entry =
                        JournalEntry::query()
                            ->with('lines')
                            ->lockForUpdate()
                            ->findOrFail(
                                $journalEntryId
                            );

                    /*
                     * Concurrency/retry safety:
                     *
                     * An entry may have become legitimately reversed after
                     * the planner classified it but before we reached it.
                     */
                    if ($entry->isReversed()) {
                        $alreadyNeutralized[] = [
                            'journal_entry_id' =>
                                (int) $entry->id,

                            'journal_number' =>
                                $entry->journal_number,

                            'reversed_by_id' =>
                                (int) $entry
                                    ->reversed_by_id,

                            'status' =>
                                'already_neutralized',
                        ];

                        continue;
                    }

                    $this->assertCandidateStillMatches(
                        $entry,
                        $candidate
                    );

                    $reversalReason =
                        sprintf(
                            'Lease deletion reversal for Lease #%d: %s',
                            (int) $lease->id,
                            $reason
                        );

                    $reversal =
                        $this->reversalService
                            ->reverse(
                                $entry,
                                $reversalReason,
                                $actorUserId
                            );

                    $reversed[] = [
                        'original_journal_entry_id' =>
                            (int) $entry->id,

                        'original_journal_number' =>
                            $entry->journal_number,

                        'reversal_journal_entry_id' =>
                            (int) $reversal->id,

                        'reversal_journal_number' =>
                            $reversal
                                ->journal_number,
                    ];
                }

                return [
                    'lease_id' =>
                        (int) $lease->id,

                    'reversed' =>
                        $reversed,

                    'already_neutralized' =>
                        $alreadyNeutralized,

                    'reversal_count' =>
                        count($reversed),

                    'already_neutralized_count' =>
                        count(
                            $alreadyNeutralized
                        ),
                ];
            }
        );
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function assertPlanExecutable(
        array $plan
    ): void {
        if (
            (
                $plan['eligibility']
                    ['safe_to_execute']
                ?? false
            ) !== true
        ) {
            $reasons =
                $plan['eligibility']
                    ['blocking_reasons']
                ?? [];

            throw new RuntimeException(
                $reasons !== []
                    ? 'Lease deletion Journal reversal is blocked: '
                        .implode(
                            ' | ',
                            $reasons
                        )
                    : 'Lease deletion Journal reversal is not authorized by the restoration plan.'
            );
        }

        if (
            (
                $plan['accounting']
                    ['shared_entries']
                ?? []
            ) !== []
        ) {
            throw new RuntimeException(
                'Lease deletion Journal reversal is blocked by shared Journal entries.'
            );
        }

        if (
            (
                $plan['accounting']
                    ['unclassified_entries']
                ?? []
            ) !== []
        ) {
            throw new RuntimeException(
                'Lease deletion Journal reversal is blocked by unclassified Journal entries.'
            );
        }

        foreach (
            $plan['accounting']
                ['opening_balance_actions']
                ?? []
            as $openingAction
        ) {
            $action =
                $openingAction['action']
                ?? null;

            /*
             * 10D2 deliberately does not invent partial/opening-balance
             * neutralization accounting.
             *
             * These actions require dedicated later treatment before the
             * final destructive executor is allowed to proceed.
             */
            if (
                $action !== null
                && $action !==
                    'no_additional_reversal'
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Lease deletion Journal reversal cannot execute opening-balance action: %s.',
                        $action
                    )
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function assertCandidateStillMatches(
        JournalEntry $entry,
        array $candidate
    ): void {
        if ($entry->isInformational()) {
            throw new RuntimeException(
                'Lease deletion cannot financially reverse an informational Journal entry.'
            );
        }

        if ($entry->isReversal()) {
            throw new RuntimeException(
                'Lease deletion cannot reverse a Journal reversal entry.'
            );
        }

        if ($entry->lines->isEmpty()) {
            throw new RuntimeException(
                'Lease deletion Journal candidate has no Journal lines.'
            );
        }

        $expectedSourceType =
            $candidate['source_type']
            ?? null;

        $expectedSourceId =
            isset($candidate['source_id'])
                ? (int) $candidate[
                    'source_id'
                ]
                : null;

        if (
            $entry->source_type
                !== $expectedSourceType
            || (
                $entry->source_id !== null
                    ? (int) $entry->source_id
                    : null
            ) !== $expectedSourceId
        ) {
            throw new RuntimeException(
                sprintf(
                    'Journal entry %s no longer matches the Lease deletion restoration plan.',
                    $entry->journal_number
                )
            );
        }

        if (
            isset(
                $candidate['journal_number']
            )
            && $candidate['journal_number']
                !== $entry->journal_number
        ) {
            throw new RuntimeException(
                'Lease deletion Journal candidate identity changed after planning.'
            );
        }
    }
}
