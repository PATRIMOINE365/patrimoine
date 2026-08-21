<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posts immutable balanced transactions into Patrimoine's Financial Journal.
 *
 * This service is the accounting boundary for V1.0.5.
 *
 * Operational financial services will later call this service inside their
 * own business transaction so:
 *
 *     business transaction + Journal posting
 *
 * either succeed together or roll back together.
 */
class JournalPostingService
{
    public function __construct(
        private readonly JournalNumberService $numbers,
        private readonly AccountingBootstrapService $bootstrap
    ) {
    }

    /**
     * Post one balanced Financial Journal transaction.
     *
     * Header keys:
     *
     * - journal_date       required YYYY-MM-DD/date-compatible value
     * - transaction_type   required non-empty string
     * - description        optional
     * - source_type        optional
     * - source_id          optional
     * - idempotency_key    optional permanent retry-safe key
     * - actor_user_id      optional
     * - actor_name_snapshot optional
     * - snapshot           optional array
     * - metadata           optional array
     *
     * Line keys:
     *
     * - account_code required system accounting account code
     * - debit        integer >= 0
     * - credit       integer >= 0
     * - memo         optional
     * - snapshot     optional array
     *
     * Exactly one of debit/credit must be greater than zero for each normal
     * accounting line.
     *
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function post(
        array $header,
        array $lines
    ): JournalEntry {
        /*
         * Financial posting is the accounting boundary.
         *
         * Ensure Patrimoine's system-managed Chart of Accounts exists
         * immediately before it is required rather than performing
         * accounting database work during every application boot.
         */
        $this->bootstrap->bootstrap();

        $normalizedHeader = $this->normalizeHeader($header);

        $normalizedLines = $this->normalizeAndValidateLines(
            $lines
        );

        return DB::transaction(
            function () use (
                $normalizedHeader,
                $normalizedLines
            ): JournalEntry {
                if ($normalizedHeader['idempotency_key'] !== null) {
                    $existing = JournalEntry::query()
                        ->with('lines')
                        ->where(
                            'idempotency_key',
                            $normalizedHeader['idempotency_key']
                        )
                        ->first();

                    if ($existing !== null) {
                        return $existing;
                    }
                }

                $journalDate = Carbon::parse(
                    $normalizedHeader['journal_date']
                );

                $journalNumber = $this->numbers->next(
                    (int) $journalDate->year
                );

                $actorName = $normalizedHeader[
                    'actor_name_snapshot'
                ];

                if (
                    $actorName === null
                    && $normalizedHeader['actor_user_id'] !== null
                ) {
                    $actorName = User::query()
                        ->whereKey(
                            $normalizedHeader['actor_user_id']
                        )
                        ->value('name');
                }

                $entry = JournalEntry::create([
                    'journal_number' => $journalNumber,
                    'journal_date' => $journalDate->toDateString(),
                    'posted_at' => now(),
                    'transaction_type' => $normalizedHeader[
                        'transaction_type'
                    ],
                    'description' => $normalizedHeader[
                        'description'
                    ],
                    'source_type' => $normalizedHeader[
                        'source_type'
                    ],
                    'source_id' => $normalizedHeader[
                        'source_id'
                    ],
                    'idempotency_key' => $normalizedHeader[
                        'idempotency_key'
                    ],
                    'actor_user_id' => $normalizedHeader[
                        'actor_user_id'
                    ],
                    'actor_name_snapshot' => $actorName,
                    'snapshot' => $normalizedHeader['snapshot'],
                    'metadata' => $normalizedHeader['metadata'],
                ]);

                foreach ($normalizedLines as $line) {
                    $account = AccountingAccount::query()
                        ->where('code', $line['account_code'])
                        ->where('is_system', true)
                        ->where('is_active', true)
                        ->first();

                    if ($account === null) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'Unknown or inactive system accounting account: %s',
                                $line['account_code']
                            )
                        );
                    }

                    JournalLine::create([
                        'journal_entry_id' => $entry->id,
                        'accounting_account_id' => $account->id,
                        'debit_amount' => $line['debit'],
                        'credit_amount' => $line['credit'],
                        'account_code_snapshot' => $account->code,
                        'account_name_snapshot' => $account->name,
                        'account_type_snapshot' => $account->type,
                        'memo' => $line['memo'],
                        'snapshot' => $line['snapshot'],
                    ]);
                }

                $entry->load('lines');

                if (! $entry->isBalanced()) {
                    /*
                     * This should be impossible because validation occurs
                     * before persistence. Keeping this invariant check inside
                     * the database transaction protects against future changes.
                     */
                    throw new InvalidArgumentException(
                        'Journal transaction is not balanced.'
                    );
                }

                return $entry;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<string, mixed>
     */
    private function normalizeHeader(array $header): array
    {
        if (! array_key_exists('journal_date', $header)) {
            throw new InvalidArgumentException(
                'Journal date is required.'
            );
        }

        $transactionType = trim(
            (string) ($header['transaction_type'] ?? '')
        );

        if ($transactionType === '') {
            throw new InvalidArgumentException(
                'Journal transaction type is required.'
            );
        }

        /*
         * Parsing here also rejects unusable date values before any Journal
         * sequence allocation or database write occurs.
         */
        $journalDate = Carbon::parse(
            $header['journal_date']
        )->toDateString();

        $sourceId = $header['source_id'] ?? null;

        if (
            $sourceId !== null
            && (! is_int($sourceId) || $sourceId <= 0)
        ) {
            throw new InvalidArgumentException(
                'Journal source ID must be a positive integer.'
            );
        }

        $actorUserId = $header['actor_user_id'] ?? null;

        if (
            $actorUserId !== null
            && (! is_int($actorUserId) || $actorUserId <= 0)
        ) {
            throw new InvalidArgumentException(
                'Journal actor User ID must be a positive integer.'
            );
        }

        return [
            'journal_date' => $journalDate,
            'transaction_type' => $transactionType,
            'description' => $this->nullableString(
                $header['description'] ?? null
            ),
            'source_type' => $this->nullableString(
                $header['source_type'] ?? null
            ),
            'source_id' => $sourceId,
            'idempotency_key' => $this->nullableString(
                $header['idempotency_key'] ?? null
            ),
            'actor_user_id' => $actorUserId,
            'actor_name_snapshot' => $this->nullableString(
                $header['actor_name_snapshot'] ?? null
            ),
            'snapshot' => $this->nullableArray(
                $header['snapshot'] ?? null,
                'Journal snapshot'
            ),
            'metadata' => $this->nullableArray(
                $header['metadata'] ?? null,
                'Journal metadata'
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAndValidateLines(
        array $lines
    ): array {
        if (count($lines) < 2) {
            throw new InvalidArgumentException(
                'A Financial Journal transaction requires at least two lines.'
            );
        }

        $normalized = [];

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $index => $line) {
            $accountCode = trim(
                (string) ($line['account_code'] ?? '')
            );

            if ($accountCode === '') {
                throw new InvalidArgumentException(
                    sprintf(
                        'Journal line %d requires an accounting account.',
                        $index + 1
                    )
                );
            }

            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;

            if (! is_int($debit) || $debit < 0) {
                throw new InvalidArgumentException(
                    'Journal debit amounts must be non-negative integers.'
                );
            }

            if (! is_int($credit) || $credit < 0) {
                throw new InvalidArgumentException(
                    'Journal credit amounts must be non-negative integers.'
                );
            }

            if (
                ($debit > 0 && $credit > 0)
                || ($debit === 0 && $credit === 0)
            ) {
                throw new InvalidArgumentException(
                    'Each Journal line must contain either a debit or a credit.'
                );
            }

            $totalDebits += $debit;
            $totalCredits += $credit;

            $normalized[] = [
                'account_code' => $accountCode,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $this->nullableString(
                    $line['memo'] ?? null
                ),
                'snapshot' => $this->nullableArray(
                    $line['snapshot'] ?? null,
                    'Journal line snapshot'
                ),
            ];
        }

        if ($totalDebits <= 0) {
            throw new InvalidArgumentException(
                'Journal transaction amount must be greater than zero.'
            );
        }

        if ($totalDebits !== $totalCredits) {
            throw new InvalidArgumentException(
                sprintf(
                    'Journal transaction is not balanced: debits=%d credits=%d.',
                    $totalDebits,
                    $totalCredits
                )
            );
        }

        return $normalized;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @return array<mixed>|null
     */
    private function nullableArray(
        mixed $value,
        string $label
    ): ?array {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException(
                $label.' must be an array.'
            );
        }

        return $value;
    }
}
