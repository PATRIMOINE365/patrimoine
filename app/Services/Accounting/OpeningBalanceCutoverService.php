<?php

namespace App\Services\Accounting;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OpeningBalanceCutoverService
{
    public const DESCRIPTION =
        'V1.0.5 Opening Balance';

    public function __construct(
        private readonly OpeningBalanceDiscoveryService $discovery,
        private readonly OpeningBalancePostingAdapter $posting
    ) {
    }

    public function execute(
        CarbonImmutable $cutoverDate,
        ?User $actor = null
    ): AccountingCutover {
        return DB::transaction(
            function () use (
                $cutoverDate,
                $actor
            ): AccountingCutover {
                $existing =
                    AccountingCutover::query()
                        ->where(
                            'cutover_key',
                            AccountingCutover::V105_OPENING_BALANCE
                        )
                        ->lockForUpdate()
                        ->first();

                if ($existing !== null) {
                    if (
                        $existing->status
                        !== AccountingCutover::STATUS_COMPLETED
                    ) {
                        throw new RuntimeException(
                            'The V1.0.5 opening-balance cutover already exists but is not completed.'
                        );
                    }

                    if (
                        $existing
                            ->cutover_date
                            ->toDateString()
                        !==
                        $cutoverDate
                            ->toDateString()
                    ) {
                        throw new RuntimeException(
                            sprintf(
                                'V1.0.5 opening balances were already posted using cutover date %s.',
                                $existing
                                    ->cutover_date
                                    ->toDateString()
                            )
                        );
                    }

                    return $existing;
                }

                /*
                 * Discovery happens inside the same outer database
                 * transaction as posting.
                 *
                 * This ensures operational balances cannot be accepted
                 * by this workflow and then partially posted by the
                 * workflow itself.
                 */
                $result =
                    $this->discovery
                        ->discover();

                $this->assertReconciled(
                    $result
                );

                $positions =
                    $this->preparePositions(
                        $result['positions'],
                        $cutoverDate
                    );

                $journalBefore =
                    JournalEntry::query()
                        ->count();

                $cutover =
                    AccountingCutover::query()
                        ->create([
                            'cutover_key' =>
                                AccountingCutover::V105_OPENING_BALANCE,

                            'cutover_date' =>
                                $cutoverDate
                                    ->toDateString(),

                            'status' =>
                                AccountingCutover::STATUS_RUNNING,

                            'position_count' =>
                                count(
                                    $positions
                                ),

                            'journal_entry_count' =>
                                0,

                            'metadata' => [
                                'description' =>
                                    self::DESCRIPTION,

                                'source_totals' =>
                                    $result[
                                        'reconciliation'
                                    ][
                                        'source_totals'
                                    ],

                                'position_totals' =>
                                    $result[
                                        'reconciliation'
                                    ][
                                        'position_totals'
                                    ],
                            ],
                        ]);

                /*
                 * The Phase 1 opening posting service is responsible
                 * for generating individually balanced immutable
                 * Journal entries using the system opening-balance
                 * clearing/equity account.
                 */
                $this->posting->post(
                    $positions,
                    $cutoverDate,
                    $actor
                );

                $journalAfter =
                    JournalEntry::query()
                        ->count();

                $created =
                    $journalAfter
                    - $journalBefore;

                if (
                    $created
                    !== count($positions)
                ) {
                    throw new RuntimeException(
                        sprintf(
                            'Opening-balance cutover expected %d Journal entries but posting created %d.',
                            count($positions),
                            $created
                        )
                    );
                }

                $cutover->forceFill([
                    'status' =>
                        AccountingCutover::STATUS_COMPLETED,

                    'journal_entry_count' =>
                        $created,

                    'completed_at' =>
                        now(),
                ])->save();

                return $cutover->fresh();
            },
            3
        );
    }

    /**
     * @param array<string,mixed> $result
     */
    private function assertReconciled(
        array $result
    ): void {
        $reconciliation =
            $result['reconciliation']
            ?? null;

        if (
            ! is_array(
                $reconciliation
            )
        ) {
            throw new RuntimeException(
                'Opening-balance discovery did not return reconciliation data.'
            );
        }

        if (
            (
                $reconciliation[
                    'all_positions_valid'
                ]
                ?? false
            ) !== true
        ) {
            throw new RuntimeException(
                'Opening-balance discovery contains invalid positions.'
            );
        }

        if (
            (
                $reconciliation[
                    'source_totals_match_positions'
                ]
                ?? false
            ) !== true
        ) {
            throw new RuntimeException(
                'Opening-balance operational reconciliation failed.'
            );
        }

        if (
            (
                $reconciliation[
                    'journal_entries_created'
                ]
                ?? null
            ) !== 0
            ||
            (
                $reconciliation[
                    'journal_lines_created'
                ]
                ?? null
            ) !== 0
        ) {
            throw new RuntimeException(
                'Opening-balance discovery mutated the Financial Journal.'
            );
        }
    }

    /**
     * @param array<int,array<string,mixed>> $positions
     * @return array<int,array<string,mixed>>
     */
    private function preparePositions(
        array $positions,
        CarbonImmutable $cutoverDate
    ): array {
        return collect(
            $positions
        )
            ->values()
            ->map(
                function (
                    array $position,
                    int $index
                ) use (
                    $cutoverDate
                ): array {
                    $position[
                        'journal_date'
                    ] =
                        $cutoverDate
                            ->toDateString();

                    $position[
                        'description'
                    ] =
                        self::DESCRIPTION;

                    /*
                     * The cutover marker is the authoritative
                     * whole-operation idempotency control.
                     *
                     * A deterministic per-position key is additionally
                     * supplied for the lower-level Journal foundation
                     * wherever it consumes position metadata.
                     */
                    $position[
                        'idempotency_key'
                    ] =
                        $this
                            ->idempotencyKey(
                                $position,
                                $index
                            );

                    $snapshot =
                        $position[
                            'snapshot'
                        ]
                        ?? [];

                    $snapshot[
                        'opening_balance'
                    ] = true;

                    $snapshot[
                        'opening_balance_version'
                    ] = 'v1.0.5';

                    $snapshot[
                        'cutover_date'
                    ] =
                        $cutoverDate
                            ->toDateString();

                    $snapshot[
                        'description'
                    ] =
                        self::DESCRIPTION;

                    $position[
                        'snapshot'
                    ] =
                        $snapshot;

                    return $position;
                }
            )
            ->all();
    }

    /**
     * @param array<string,mixed> $position
     */
    private function idempotencyKey(
        array $position,
        int $index
    ): string {
        $identity = [
            'version' =>
                'v1.0.5',

            'category' =>
                $position[
                    'category'
                ]
                ?? null,

            'account_code' =>
                $position[
                    'account_code'
                ]
                ?? null,

            'direction' =>
                $position[
                    'direction'
                ]
                ?? null,

            'amount' =>
                $position[
                    'amount'
                ]
                ?? null,

            'source_type' =>
                $position[
                    'source_type'
                ]
                ?? null,

            'source_id' =>
                $position[
                    'source_id'
                ]
                ?? null,

            'index' =>
                $index,
        ];

        return
            'v105-opening:'
            .hash(
                'sha256',
                json_encode(
                    $identity,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                )
            );
    }
}
