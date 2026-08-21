<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posts the V1.0.5 accounting opening position.
 *
 * Phase 2 will calculate the actual production balances and supply them to
 * this service. This Phase 1 service only provides the safe posting mechanism.
 */
class OpeningBalancePostingService
{
    public const TRANSACTION_TYPE =
        'v1_0_5_opening_balance';

    public const DESCRIPTION =
        'V1.0.5 Opening Balance';

    public function __construct(
        private readonly JournalPostingService $postingService
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, JournalEntry>
     */
    public function post(
        array $positions,
        string $journalDate
    ): array {
        if ($positions === []) {
            return [];
        }

        return DB::transaction(function () use (
            $positions,
            $journalDate
        ): array {
            $entries = [];

            foreach ($positions as $position) {
                $accountCode = trim(
                    (string) ($position['account_code'] ?? '')
                );

                $direction = trim(
                    (string) ($position['direction'] ?? '')
                );

                $amount = $position['amount'] ?? null;

                if ($accountCode === '') {
                    throw new InvalidArgumentException(
                        'Opening balance account code is required.'
                    );
                }

                if (
                    ! in_array(
                        $direction,
                        ['debit', 'credit'],
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Opening balance direction must be debit or credit.'
                    );
                }

                if (
                    ! is_int($amount)
                    || $amount <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Opening balance amount must be a positive integer.'
                    );
                }

                $counterDirection =
                    $direction === 'debit'
                        ? 'credit'
                        : 'debit';

                $entries[] = $this->postingService->post(
                    [
                        'journal_date' =>
                            $journalDate,

                        'transaction_type' =>
                            self::TRANSACTION_TYPE,

                        'description' =>
                            self::DESCRIPTION,

                        'source_type' =>
                            $position['source_type'] ?? null,

                        'source_id' =>
                            $position['source_id'] ?? null,

                        'snapshot' =>
                            $position['snapshot'] ?? null,

                        'metadata' => [
                            'opening_balance' => true,
                            'opening_account_code' =>
                                $accountCode,
                        ],
                    ],
                    [
                        [
                            'account_code' =>
                                $accountCode,

                            'debit' =>
                                $direction === 'debit'
                                    ? $amount
                                    : 0,

                            'credit' =>
                                $direction === 'credit'
                                    ? $amount
                                    : 0,

                            'memo' =>
                                self::DESCRIPTION,
                        ],
                        [
                            'account_code' =>
                                SystemChartOfAccounts::OPENING_BALANCE_CLEARING,

                            'debit' =>
                                $counterDirection === 'debit'
                                    ? $amount
                                    : 0,

                            'credit' =>
                                $counterDirection === 'credit'
                                    ? $amount
                                    : 0,

                            'memo' =>
                                self::DESCRIPTION,
                        ],
                    ]
                );
            }

            return $entries;
        });
    }
}
