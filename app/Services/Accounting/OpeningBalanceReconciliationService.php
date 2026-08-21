<?php

namespace App\Services\Accounting;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Read-only V1.0.5 opening-balance reconciliation.
 *
 * This service never creates, edits or deletes Journal or operational
 * financial records. Its only responsibility is to verify that the
 * controlled V1.0.5 opening cutover agrees with the operational opening
 * position and with double-entry accounting invariants.
 *
 * This reconciliation is intended to be run immediately around cutover,
 * before ordinary post-cutover financial activity changes the operational
 * balances away from their opening position.
 */
class OpeningBalanceReconciliationService
{
    public function __construct(
        private readonly OpeningBalanceDiscoveryService $discovery,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function reconcile(): array
    {
        $before = $this->journalCounts();

        $discovery = $this->discovery->discover();

        $positions = collect(
            $discovery['positions'] ?? []
        );

        $cutover = AccountingCutover::query()
            ->latest('id')
            ->first();

        $openingEntries = JournalEntry::query()
            ->with('lines')
            ->where(
                'description',
                OpeningBalanceCutoverService::DESCRIPTION
            )
            ->orderBy('id')
            ->get();

        $expectedByAccount =
            $this->expectedNetByAccount($positions);

        $actualByAccount =
            $this->actualNetByAccount(
                $openingEntries,
                $expectedByAccount->keys()
            );

        $accountComparison =
            $this->compareAccounts(
                $expectedByAccount,
                $actualByAccount
            );

        $entryBalanceChecks =
            $openingEntries
                ->map(
                    fn (JournalEntry $entry): array =>
                        $this->entryBalanceCheck($entry)
                )
                ->values();

        $allEntriesBalanced =
            $entryBalanceChecks
                ->every(
                    fn (array $check): bool =>
                        $check['balanced'] === true
                );

        $expectedPositionCount =
            $positions->count();

        $openingEntryCount =
            $openingEntries->count();

        /*
         * OpeningBalancePostingService intentionally posts one balanced
         * Journal entry per actual opening position.
         */
        $positionCountMatches =
            $expectedPositionCount ===
            $openingEntryCount;

        $accountTotalsMatch =
            $accountComparison
                ->every(
                    fn (array $row): bool =>
                        $row['matches'] === true
                );

        $discoveryReconciled =
            (bool) data_get(
                $discovery,
                'reconciliation.source_totals_match_positions',
                data_get(
                    $discovery,
                    'source_totals_match_positions',
                    true
                )
            );

        $cutoverInitialized =
            $cutover !== null;

        /*
         * If there is no cutover record, this is a valid pre-cutover
         * diagnostic state but NOT a successful cutover reconciliation.
         */
        $reconciled =
            $cutoverInitialized
            && $discoveryReconciled
            && $positionCountMatches
            && $accountTotalsMatch
            && $allEntriesBalanced;

        $after = $this->journalCounts();

        if ($before !== $after) {
            throw new RuntimeException(
                'Opening-balance reconciliation must be strictly read-only.'
            );
        }

        return [
            'status' =>
                $cutoverInitialized
                    ? ($reconciled
                        ? 'reconciled'
                        : 'failed')
                    : 'not_initialized',

            'reconciled' =>
                $reconciled,

            'cutover_initialized' =>
                $cutoverInitialized,

            'cutover' =>
                $cutover === null
                    ? null
                    : [
                        'id' =>
                            $cutover->getKey(),

                        'attributes' =>
                            $this->safeCutoverAttributes(
                                $cutover
                            ),
                    ],

            'operational_discovery' => [
                'positions' =>
                    $expectedPositionCount,

                'source_totals_match_positions' =>
                    $discoveryReconciled,

                'source_totals' =>
                    $discovery['source_totals']
                    ?? data_get(
                        $discovery,
                        'reconciliation.source_totals',
                        []
                    ),

                'position_totals' =>
                    $discovery['position_totals']
                    ?? data_get(
                        $discovery,
                        'reconciliation.position_totals',
                        []
                    ),
            ],

            'opening_journal' => [
                'entries' =>
                    $openingEntryCount,

                'position_count_matches' =>
                    $positionCountMatches,

                'all_entries_balanced' =>
                    $allEntriesBalanced,

                'account_totals_match' =>
                    $accountTotalsMatch,
            ],

            'accounts' =>
                $accountComparison
                    ->values()
                    ->all(),

            'entries' =>
                $entryBalanceChecks
                    ->all(),

            'journal_mutation_check' => [
                'before' =>
                    $before,

                'after' =>
                    $after,

                'unchanged' =>
                    $before === $after,
            ],
        ];
    }

    /**
     * Convert opening positions into signed debit/credit net values.
     *
     * Debit is positive.
     * Credit is negative.
     *
     * @param Collection<int, mixed> $positions
     * @return Collection<string, int>
     */
    private function expectedNetByAccount(
        Collection $positions
    ): Collection {
        $totals = [];

        foreach ($positions as $position) {
            if (! is_array($position)) {
                throw new RuntimeException(
                    'Invalid opening position discovered.'
                );
            }

            $account =
                $position['account_code']
                ?? $position['account']
                ?? $position['code']
                ?? null;

            $direction =
                $position['direction']
                ?? null;

            $amount =
                $position['amount']
                ?? null;

            if (
                ! is_string($account)
                || trim($account) === ''
                || ! in_array(
                    $direction,
                    ['debit', 'credit'],
                    true
                )
                || ! is_numeric($amount)
            ) {
                throw new RuntimeException(
                    'Opening position is missing account, direction or amount.'
                );
            }

            $amount = (int) $amount;

            if ($amount < 0) {
                throw new RuntimeException(
                    'Opening position amount cannot be negative.'
                );
            }

            $net =
                $direction === 'debit'
                    ? $amount
                    : -$amount;

            $totals[$account] =
                ($totals[$account] ?? 0)
                + $net;
        }

        ksort($totals);

        return collect($totals);
    }

    /**
     * Calculate the actual opening Journal net effect only for real
     * operational accounts expected by discovery.
     *
     * The balancing/equity side of opening entries is deliberately ignored
     * here because it is not itself an operational balance discovered from
     * Patrimoine's pre-cutover business records.
     *
     * @param Collection<int, JournalEntry> $entries
     * @param Collection<int, string> $expectedAccounts
     * @return Collection<string, int>
     */
    private function actualNetByAccount(
        Collection $entries,
        Collection $expectedAccounts
    ): Collection {
        $accountSet =
            array_fill_keys(
                $expectedAccounts->all(),
                true
            );

        $totals = [];

        foreach ($entries as $entry) {
            foreach ($entry->lines as $line) {
                $code =
                    (string)
                    $line->account_code_snapshot;

                if (! isset($accountSet[$code])) {
                    continue;
                }

                $debit =
                    (int)
                    $line->debit_amount;

                $credit =
                    (int)
                    $line->credit_amount;

                $totals[$code] =
                    ($totals[$code] ?? 0)
                    + $debit
                    - $credit;
            }
        }

        foreach ($expectedAccounts as $code) {
            $totals[$code] =
                $totals[$code] ?? 0;
        }

        ksort($totals);

        return collect($totals);
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function compareAccounts(
        Collection $expected,
        Collection $actual
    ): Collection {
        $codes =
            $expected
                ->keys()
                ->merge(
                    $actual->keys()
                )
                ->unique()
                ->sort()
                ->values();

        return $codes->mapWithKeys(
            function (string $code) use (
                $expected,
                $actual
            ): array {
                $expectedNet =
                    (int)
                    $expected->get(
                        $code,
                        0
                    );

                $actualNet =
                    (int)
                    $actual->get(
                        $code,
                        0
                    );

                return [
                    $code => [
                        'account' =>
                            $code,

                        'expected_net' =>
                            $expectedNet,

                        'actual_net' =>
                            $actualNet,

                        'difference' =>
                            $actualNet
                            - $expectedNet,

                        'matches' =>
                            $expectedNet
                            === $actualNet,
                    ],
                ];
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function entryBalanceCheck(
        JournalEntry $entry
    ): array {
        $debit =
            (int)
            $entry->lines
                ->sum(
                    'debit_amount'
                );

        $credit =
            (int)
            $entry->lines
                ->sum(
                    'credit_amount'
                );

        return [
            'id' =>
                $entry->getKey(),

            'journal_number' =>
                $entry->journal_number,

            'debit' =>
                $debit,

            'credit' =>
                $credit,

            'difference' =>
                $debit - $credit,

            'balanced' =>
                $debit === $credit,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function journalCounts(): array
    {
        return [
            'entries' =>
                JournalEntry::query()->count(),

            'lines' =>
                \App\Models\JournalLine::query()->count(),
        ];
    }

    /**
     * Keep reconciliation output safe regardless of future model casts.
     *
     * @return array<string, mixed>
     */
    private function safeCutoverAttributes(
        AccountingCutover $cutover
    ): array {
        return collect(
            $cutover->toArray()
        )
            ->except([
                'created_by_password',
                'password',
                'token',
            ])
            ->all();
    }
}
