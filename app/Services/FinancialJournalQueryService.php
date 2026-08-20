<?php

namespace App\Services;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Canonical read/query contract for Patrimoine's immutable Financial Journal.
 *
 * The browser register, future exports and other read-only Journal consumers
 * must use the same filter validation and query semantics.
 */
class FinancialJournalQueryService
{
    /**
     * Validate Financial Journal filters.
     *
     * @return array<string, mixed>
     */
    public function validatedFilters(
        Request $request,
        bool $includePagination = true
    ): array {
        $rules = [
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],

            'journal_number' => [
                'nullable',
                'string',
                'max:32',
            ],

            'entry_kind' => [
                'nullable',
                Rule::in([
                    JournalEntry::KIND_FINANCIAL,
                    JournalEntry::KIND_REVERSAL,
                    JournalEntry::KIND_INFORMATIONAL,
                ]),
            ],

            'transaction_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'account_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'actor_user_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'source_type' => [
                'nullable',
                'string',
                'max:150',
            ],

            'source_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        if ($includePagination) {
            $rules['page'] = [
                'nullable',
                'integer',
                'min:1',
            ];

            $rules['per_page'] = [
                'nullable',
                'integer',
                'between:1,100',
            ];
        }

        return $request->validate(
            $rules
        );
    }

    /**
     * Build the authoritative filtered Financial Journal query.
     *
     * @param array<string, mixed> $filters
     * @return Builder<JournalEntry>
     */
    public function query(
        array $filters
    ): Builder {
        $query =
            JournalEntry::query();

        if (isset($filters['from'])) {
            $query->whereDate(
                'journal_date',
                '>=',
                $filters['from']
            );
        }

        if (isset($filters['to'])) {
            $query->whereDate(
                'journal_date',
                '<=',
                $filters['to']
            );
        }

        if (isset($filters['journal_number'])) {
            $query->where(
                'journal_number',
                $filters['journal_number']
            );
        }

        if (isset($filters['entry_kind'])) {
            $query->where(
                'entry_kind',
                $filters['entry_kind']
            );
        }

        if (isset($filters['transaction_type'])) {
            $query->where(
                'transaction_type',
                $filters['transaction_type']
            );
        }

        if (isset($filters['actor_user_id'])) {
            $query->where(
                'actor_user_id',
                $filters['actor_user_id']
            );
        }

        if (isset($filters['source_type'])) {
            $query->where(
                'source_type',
                $filters['source_type']
            );
        }

        if (isset($filters['source_id'])) {
            $query->where(
                'source_id',
                $filters['source_id']
            );
        }

        if (isset($filters['account_id'])) {
            $accountId =
                (int) $filters['account_id'];

            $query->whereHas(
                'lines',
                function (
                    Builder $lineQuery
                ) use (
                    $accountId
                ): void {
                    $lineQuery->where(
                        'accounting_account_id',
                        $accountId
                    );
                }
            );
        }

        if (
            isset($filters['search'])
            && trim(
                (string) $filters['search']
            ) !== ''
        ) {
            $search =
                trim(
                    (string) $filters['search']
                );

            $pattern =
                "%{$search}%";

            $query->where(
                function (
                    Builder $entryQuery
                ) use (
                    $pattern
                ): void {
                    $entryQuery
                        ->where(
                            'journal_number',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'transaction_type',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'description',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'source_type',
                            'like',
                            $pattern
                        )
                        ->orWhereRaw(
                            'CAST(source_id AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhere(
                            'actor_name_snapshot',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'reversal_reason',
                            'like',
                            $pattern
                        )
                        ->orWhereRaw(
                            'CAST(snapshot AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'CAST(metadata AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereHas(
                            'lines',
                            function (
                                Builder $lineQuery
                            ) use (
                                $pattern
                            ): void {
                                $lineQuery
                                    ->where(
                                        'account_code_snapshot',
                                        'like',
                                        $pattern
                                    )
                                    ->orWhere(
                                        'account_name_snapshot',
                                        'like',
                                        $pattern
                                    )
                                    ->orWhere(
                                        'account_type_snapshot',
                                        'like',
                                        $pattern
                                    )
                                    ->orWhere(
                                        'memo',
                                        'like',
                                        $pattern
                                    )
                                    ->orWhereRaw(
                                        'CAST(snapshot AS CHAR) LIKE ?',
                                        [$pattern]
                                    );
                            }
                        );
                }
            );
        }

        return $query
            ->orderByDesc(
                'journal_date'
            )
            ->orderByDesc(
                'posted_at'
            )
            ->orderByDesc(
                'id'
            );
    }

    /**
     * Return the material filters for future export reuse.
     *
     * Pagination is intentionally excluded.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function exportFilterSnapshot(
        array $filters
    ): array {
        return array_filter(
            [
                'from' =>
                    $filters['from']
                    ?? null,

                'to' =>
                    $filters['to']
                    ?? null,

                'journal_number' =>
                    $filters['journal_number']
                    ?? null,

                'entry_kind' =>
                    $filters['entry_kind']
                    ?? null,

                'transaction_type' =>
                    $filters['transaction_type']
                    ?? null,

                'account_id' =>
                    $filters['account_id']
                    ?? null,

                'actor_user_id' =>
                    $filters['actor_user_id']
                    ?? null,

                'source_type' =>
                    $filters['source_type']
                    ?? null,

                'source_id' =>
                    $filters['source_id']
                    ?? null,

                'search' =>
                    $filters['search']
                    ?? null,
            ],
            static fn (
                mixed $value
            ): bool =>
                $value !== null
                && $value !== ''
        );
    }
}
