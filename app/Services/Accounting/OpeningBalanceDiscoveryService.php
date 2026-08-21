<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\OwnerAccount;
use App\Models\TenantFundAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Read-only V1.0.5 opening-position calculator.
 *
 * IMPORTANT:
 * - This service never posts Journal entries.
 * - This service never mutates operational financial records.
 * - It derives opening positions from Patrimoine's current operational state.
 *
 * Phase 2 Step 2 will consume the normalized output only after Step 1
 * reconciliation has been accepted.
 */
class OpeningBalanceDiscoveryService
{
    /**
     * @return array{
     *     generated_at:string,
     *     positions:array<int, array<string,mixed>>,
     *     totals:array<string, array{debit:int,credit:int,net:int}>,
     *     counts:array<string,int>,
     *     reconciliation:array<string,mixed>
     * }
     */
    public function discover(): array
    {
        return DB::transaction(function (): array {
            /*
             * Discovery is strictly read-only.
             *
             * Capture Journal counts before calculation so reconciliation
             * reports mutations caused by this discovery run, not existing
             * Journal records that may legitimately exist later.
             */
            $journalEntriesBefore = DB::table(
                'journal_entries'
            )->count();

            $journalLinesBefore = DB::table(
                'journal_lines'
            )->count();

            $positions = collect()
                ->concat($this->discoverTenantFunds())
                ->concat($this->discoverOwnerBalances())
                ->concat($this->discoverReceivables())
                ->values();

            $totals = $this->totalsByAccount($positions);

            /*
             * Re-query the operational sources and calculate their totals
             * independently from the normalized output.
             *
             * This catches dropped, duplicated, or misclassified opening
             * positions before any posting is allowed.
             */
            $sourceTotals = $this->operationalSourceTotals();

            $positionTotals = $this->normalizedPositionTotals(
                $positions
            );

            return [
                'generated_at' => now()->toIso8601String(),

                'positions' => $positions->all(),

                'totals' => $totals,

                'counts' => [
                    'positions' => $positions->count(),

                    'tenant_funds' => $positions
                        ->where('category', 'tenant_fund')
                        ->count(),

                    'owner_balances' => $positions
                        ->where('category', 'owner_balance')
                        ->count(),

                    'rent_receivables' => $positions
                        ->where('category', 'rent_receivable')
                        ->count(),

                    'security_deposit_debt_receivables' => $positions
                        ->where(
                            'category',
                            'security_deposit_debt_receivable'
                        )
                        ->count(),
                ],

                'reconciliation' => [
                    /*
                     * Every normalized position must have exactly one
                     * accounting side and a positive amount.
                     */
                    'all_positions_valid' =>
                        $positions->every(
                            fn (array $position): bool =>
                                in_array(
                                    $position['direction'],
                                    ['debit', 'credit'],
                                    true
                                )
                                && is_int($position['amount'])
                                && $position['amount'] > 0
                        ),

                    'source_totals' =>
                        $sourceTotals,

                    'position_totals' =>
                        $positionTotals,

                    'source_totals_match_positions' =>
                        $sourceTotals === $positionTotals,

                    'journal_entries_created' =>
                        DB::table('journal_entries')->count()
                        - $journalEntriesBefore,

                    'journal_lines_created' =>
                        DB::table('journal_lines')->count()
                        - $journalLinesBefore,
                ],
            ];
        });
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    private function discoverTenantFunds(): Collection
    {
        if (! class_exists(TenantFundAccount::class)) {
            throw new RuntimeException(
                'TenantFundAccount model is required for opening-balance discovery.'
            );
        }

        return TenantFundAccount::query()
            ->get()
            ->map(function (TenantFundAccount $account): ?array {
                $balance = $this->resolveBalance(
                    $account,
                    'Tenant fund account'
                );

                if ($balance === 0) {
                    return null;
                }

                $fundType = $this->resolveStringAttribute(
                    $account,
                    [
                        'type',
                        'fund_type',
                        'account_type',
                    ],
                    'Tenant fund type'
                );

                $accountCode = app(
                    AccountingEventMap::class
                )->tenantFundLiability($fundType);

                /*
                 * Tenant funds normally represent liabilities to tenants.
                 * Positive operational fund balance therefore opens as
                 * a credit liability. A negative balance, if the current
                 * operational model permits one, opens as a debit.
                 */
                $direction = $balance > 0
                    ? 'credit'
                    : 'debit';

                $lease = $this->relatedModel(
                    $account,
                    'lease'
                );

                $tenant = $lease !== null
                    ? $this->relatedModel($lease, 'tenant')
                    : null;

                $unit = $lease !== null
                    ? $this->relatedModel($lease, 'unit')
                    : null;

                $building = $unit !== null
                    ? $this->relatedModel($unit, 'building')
                    : null;

                return [
                    'category' =>
                        'tenant_fund',

                    'account_code' =>
                        $accountCode,

                    'direction' =>
                        $direction,

                    'amount' =>
                        abs($balance),

                    'source_type' =>
                        TenantFundAccount::class,

                    'source_id' =>
                        $account->getKey(),

                    'snapshot' => [
                        'fund_type' =>
                            $fundType,

                        'tenant_fund_account_id' =>
                            $account->getKey(),

                        'lease_id' =>
                            $lease?->getKey(),

                        'lease_reference' =>
                            $this->displayReference($lease),

                        'tenant_id' =>
                            $tenant?->getKey(),

                        'tenant_name' =>
                            $this->displayName($tenant),

                        'unit_id' =>
                            $unit?->getKey(),

                        'unit_name' =>
                            $this->displayName($unit),

                        'building_id' =>
                            $building?->getKey(),

                        'building_name' =>
                            $this->displayName($building),

                        'operational_balance' =>
                            $balance,
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    private function discoverOwnerBalances(): Collection
    {
        if (! class_exists(OwnerAccount::class)) {
            throw new RuntimeException(
                'OwnerAccount model is required for opening-balance discovery.'
            );
        }

        return OwnerAccount::query()
            ->get()
            ->map(function (OwnerAccount $account): ?array {
                $balance = $this->resolveBalance(
                    $account,
                    'Owner account'
                );

                if ($balance === 0) {
                    return null;
                }

                $owner = $this->relatedModel(
                    $account,
                    'owner'
                );

                /*
                 * Positive Owner balance means Patrimoine owes the Owner:
                 * credit Owner Funds Payable.
                 *
                 * Negative Owner balance means the Owner owes/has consumed
                 * beyond available balance:
                 * debit Owner Funds Payable.
                 */
                return [
                    'category' =>
                        'owner_balance',

                    'account_code' =>
                        SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                    'direction' =>
                        $balance > 0
                            ? 'credit'
                            : 'debit',

                    'amount' =>
                        abs($balance),

                    'source_type' =>
                        OwnerAccount::class,

                    'source_id' =>
                        $account->getKey(),

                    'snapshot' => [
                        'owner_account_id' =>
                            $account->getKey(),

                        'owner_id' =>
                            $owner?->getKey(),

                        'owner_name' =>
                            $this->displayName($owner),

                        'operational_balance' =>
                            $balance,
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    private function discoverReceivables(): Collection
    {
        if (! class_exists(Invoice::class)) {
            throw new RuntimeException(
                'Invoice model is required for opening-balance discovery.'
            );
        }

        return Invoice::query()
            ->get()
            ->map(function (Invoice $invoice): ?array {
                if ($this->invoiceIsCancelled($invoice)) {
                    return null;
                }

                $outstanding = $this->resolveInvoiceOutstanding(
                    $invoice
                );

                if ($outstanding <= 0) {
                    return null;
                }

                $type = $this->invoiceType($invoice);

                $isSecurityDepositDebt =
                    $type === 'security_deposit_debt';

                $lease = $this->relatedModel(
                    $invoice,
                    'lease'
                );

                $tenant = $lease !== null
                    ? $this->relatedModel($lease, 'tenant')
                    : null;

                $unit = $lease !== null
                    ? $this->relatedModel($lease, 'unit')
                    : null;

                $building = $unit !== null
                    ? $this->relatedModel($unit, 'building')
                    : null;

                return [
                    'category' =>
                        $isSecurityDepositDebt
                            ? 'security_deposit_debt_receivable'
                            : 'rent_receivable',

                    'account_code' =>
                        $isSecurityDepositDebt
                            ? SystemChartOfAccounts::SECURITY_DEPOSIT_DEBT_RECEIVABLE
                            : SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                    'direction' =>
                        'debit',

                    'amount' =>
                        $outstanding,

                    'source_type' =>
                        Invoice::class,

                    'source_id' =>
                        $invoice->getKey(),

                    'snapshot' => [
                        'invoice_id' =>
                            $invoice->getKey(),

                        'invoice_reference' =>
                            $this->displayReference($invoice),

                        'invoice_type' =>
                            $type,

                        'lease_id' =>
                            $lease?->getKey(),

                        'lease_reference' =>
                            $this->displayReference($lease),

                        'tenant_id' =>
                            $tenant?->getKey(),

                        'tenant_name' =>
                            $this->displayName($tenant),

                        'unit_id' =>
                            $unit?->getKey(),

                        'unit_name' =>
                            $this->displayName($unit),

                        'building_id' =>
                            $building?->getKey(),

                        'building_name' =>
                            $this->displayName($building),

                        'outstanding_amount' =>
                            $outstanding,
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Resolve existing operational balance without reimplementing ledger
     * accounting when the domain model already exposes that behaviour.
     */
    private function resolveBalance(
        Model $model,
        string $label
    ): int {
        foreach (
            [
                'balance',
                'currentBalance',
                'getBalance',
            ]
            as $method
        ) {
            if (! method_exists($model, $method)) {
                continue;
            }

            $value = $model->{$method}();

            if (! is_numeric($value)) {
                throw new RuntimeException(
                    "{$label} {$method}() did not return a numeric balance."
                );
            }

            return (int) $value;
        }

        foreach (
            [
                'balance',
                'current_balance',
            ]
            as $attribute
        ) {
            if (
                array_key_exists(
                    $attribute,
                    $model->getAttributes()
                )
            ) {
                return (int) $model->getAttribute(
                    $attribute
                );
            }
        }

        throw new RuntimeException(
            sprintf(
                '%s model %s does not expose a recognized balance API.',
                $label,
                $model::class
            )
        );
    }

    private function resolveInvoiceOutstanding(
        Invoice $invoice
    ): int {
        foreach (
            [
                'outstandingAmount',
                'balanceDue',
                'balance',
            ]
            as $method
        ) {
            if (! method_exists($invoice, $method)) {
                continue;
            }

            $value = $invoice->{$method}();

            if (! is_numeric($value)) {
                throw new RuntimeException(
                    sprintf(
                        'Invoice %s::%s() did not return a numeric value.',
                        $invoice::class,
                        $method
                    )
                );
            }

            return max(0, (int) $value);
        }

        foreach (
            [
                'outstanding_amount',
                'balance_due',
                'balance',
            ]
            as $attribute
        ) {
            if (
                array_key_exists(
                    $attribute,
                    $invoice->getAttributes()
                )
            ) {
                return max(
                    0,
                    (int) $invoice->getAttribute(
                        $attribute
                    )
                );
            }
        }

        /*
         * Last safe fallback:
         * total invoice amount minus persisted allocations.
         *
         * We use only recognized persisted amount fields and the existing
         * allocations relationship. If either is unavailable, fail closed.
         */
        $total = null;

        foreach (
            [
                'total_amount',
                'total',
                'amount',
            ]
            as $attribute
        ) {
            if (
                array_key_exists(
                    $attribute,
                    $invoice->getAttributes()
                )
            ) {
                $total = (int) $invoice->getAttribute(
                    $attribute
                );

                break;
            }
        }

        if ($total === null) {
            throw new RuntimeException(
                sprintf(
                    'Invoice %s does not expose a recognized total/outstanding API.',
                    $invoice->getKey()
                )
            );
        }

        if (! method_exists($invoice, 'allocations')) {
            throw new RuntimeException(
                sprintf(
                    'Invoice %s does not expose allocations(); refusing to infer outstanding balance.',
                    $invoice->getKey()
                )
            );
        }

        $allocated = $invoice
            ->allocations()
            ->get()
            ->sum(function (Model $allocation): int {
                foreach (
                    [
                        'amount',
                        'allocated_amount',
                    ]
                    as $attribute
                ) {
                    if (
                        array_key_exists(
                            $attribute,
                            $allocation->getAttributes()
                        )
                    ) {
                        return (int) $allocation
                            ->getAttribute($attribute);
                    }
                }

                throw new RuntimeException(
                    'Payment allocation does not expose a recognized amount field.'
                );
            });

        return max(
            0,
            $total - (int) $allocated
        );
    }

    private function invoiceIsCancelled(
        Invoice $invoice
    ): bool {
        $status = strtolower(
            trim(
                (string) (
                    $invoice->getAttribute('status')
                    ?? ''
                )
            )
        );

        return in_array(
            $status,
            [
                'cancelled',
                'canceled',
                'void',
                'voided',
            ],
            true
        );
    }

    private function invoiceType(
        Invoice $invoice
    ): string {
        foreach (
            [
                'invoice_type',
                'type',
            ]
            as $attribute
        ) {
            if (
                array_key_exists(
                    $attribute,
                    $invoice->getAttributes()
                )
            ) {
                $value = trim(
                    (string) $invoice
                        ->getAttribute($attribute)
                );

                if ($value !== '') {
                    if (
                        ! in_array(
                            $value,
                            [
                                'rent',
                                'security_deposit_debt',
                            ],
                            true
                        )
                    ) {
                        throw new RuntimeException(
                            sprintf(
                                'Unsupported invoice type %s on invoice %s; refusing to classify opening receivable.',
                                $value,
                                $invoice->getKey()
                            )
                        );
                    }

                    return $value;
                }
            }
        }

        /*
         * Existing pre-V1.0.1 invoices may implicitly be rent.
         */
        return 'rent';
    }

    /**
     * Calculate source balances independently from normalized positions.
     *
     * Liability-style operational balances use the application's natural
     * sign: positive means money held/payable; negative means the opposite.
     *
     * Receivables are positive outstanding amounts.
     *
     * @return array<string,int>
     */
    private function operationalSourceTotals(): array
    {
        $totals = [
            'tenant_fund:rent_reserve' =>
                0,

            'tenant_fund:consumable_advance' =>
                0,

            'tenant_fund:security_deposit' =>
                0,

            'owner_balance' =>
                0,

            'rent_receivable' =>
                0,

            'security_deposit_debt_receivable' =>
                0,
        ];

        TenantFundAccount::query()
            ->get()
            ->each(function (
                TenantFundAccount $account
            ) use (&$totals): void {
                $balance = $this->resolveBalance(
                    $account,
                    'Tenant fund account'
                );

                if ($balance === 0) {
                    return;
                }

                $fundType =
                    $this->resolveStringAttribute(
                        $account,
                        [
                            'type',
                            'fund_type',
                            'account_type',
                        ],
                        'Tenant fund type'
                    );

                $key =
                    'tenant_fund:'.$fundType;

                if (! array_key_exists($key, $totals)) {
                    throw new RuntimeException(
                        sprintf(
                            'Unsupported Tenant fund type %s during opening-balance reconciliation.',
                            $fundType
                        )
                    );
                }

                $totals[$key] +=
                    $balance;
            });

        OwnerAccount::query()
            ->get()
            ->each(function (
                OwnerAccount $account
            ) use (&$totals): void {
                $totals['owner_balance'] +=
                    $this->resolveBalance(
                        $account,
                        'Owner account'
                    );
            });

        Invoice::query()
            ->get()
            ->each(function (
                Invoice $invoice
            ) use (&$totals): void {
                if (
                    $this->invoiceIsCancelled(
                        $invoice
                    )
                ) {
                    return;
                }

                $outstanding =
                    $this->resolveInvoiceOutstanding(
                        $invoice
                    );

                if ($outstanding <= 0) {
                    return;
                }

                $type =
                    $this->invoiceType(
                        $invoice
                    );

                if (
                    $type ===
                    'security_deposit_debt'
                ) {
                    $totals[
                        'security_deposit_debt_receivable'
                    ] += $outstanding;

                    return;
                }

                $totals[
                    'rent_receivable'
                ] += $outstanding;
            });

        ksort($totals);

        return $totals;
    }

    /**
     * Reconstruct the same operational totals from normalized positions.
     *
     * @param Collection<int,array<string,mixed>> $positions
     * @return array<string,int>
     */
    private function normalizedPositionTotals(
        Collection $positions
    ): array {
        $totals = [
            'tenant_fund:rent_reserve' =>
                0,

            'tenant_fund:consumable_advance' =>
                0,

            'tenant_fund:security_deposit' =>
                0,

            'owner_balance' =>
                0,

            'rent_receivable' =>
                0,

            'security_deposit_debt_receivable' =>
                0,
        ];

        foreach ($positions as $position) {
            $category =
                $position['category'];

            if ($category === 'tenant_fund') {
                $fundType =
                    $position['snapshot'][
                        'fund_type'
                    ];

                $key =
                    'tenant_fund:'.$fundType;

                if (! array_key_exists($key, $totals)) {
                    throw new RuntimeException(
                        sprintf(
                            'Unsupported normalized Tenant fund type %s.',
                            $fundType
                        )
                    );
                }

                /*
                 * Tenant liability normalization:
                 * credit = positive operational balance;
                 * debit  = negative operational balance.
                 */
                $totals[$key] +=
                    $position['direction'] ===
                    'credit'
                        ? $position['amount']
                        : -$position['amount'];

                continue;
            }

            if ($category === 'owner_balance') {
                $totals['owner_balance'] +=
                    $position['direction'] ===
                    'credit'
                        ? $position['amount']
                        : -$position['amount'];

                continue;
            }

            if (
                $category ===
                'rent_receivable'
            ) {
                $totals[
                    'rent_receivable'
                ] += $position['amount'];

                continue;
            }

            if (
                $category ===
                'security_deposit_debt_receivable'
            ) {
                $totals[
                    'security_deposit_debt_receivable'
                ] += $position['amount'];

                continue;
            }

            throw new RuntimeException(
                sprintf(
                    'Unsupported normalized opening-balance category %s.',
                    $category
                )
            );
        }

        ksort($totals);

        return $totals;
    }

    /**
     * @param  Collection<int,array<string,mixed>>  $positions
     * @return array<string,array{debit:int,credit:int,net:int}>
     */
    private function totalsByAccount(
        Collection $positions
    ): array {
        return $positions
            ->groupBy('account_code')
            ->map(function (Collection $rows): array {
                $debit = (int) $rows
                    ->where('direction', 'debit')
                    ->sum('amount');

                $credit = (int) $rows
                    ->where('direction', 'credit')
                    ->sum('amount');

                return [
                    'debit' =>
                        $debit,

                    'credit' =>
                        $credit,

                    /*
                     * Positive = debit position.
                     * Negative = credit position.
                     */
                    'net' =>
                        $debit - $credit,
                ];
            })
            ->sortKeys()
            ->all();
    }

    private function resolveStringAttribute(
        Model $model,
        array $candidates,
        string $label
    ): string {
        foreach ($candidates as $attribute) {
            if (
                ! array_key_exists(
                    $attribute,
                    $model->getAttributes()
                )
            ) {
                continue;
            }

            $value = trim(
                (string) $model->getAttribute(
                    $attribute
                )
            );

            if ($value !== '') {
                return $value;
            }
        }

        throw new RuntimeException(
            sprintf(
                '%s is unavailable on %s #%s.',
                $label,
                $model::class,
                $model->getKey()
            )
        );
    }

    private function relatedModel(
        Model $model,
        string $relationship
    ): ?Model {
        if (! method_exists($model, $relationship)) {
            return null;
        }

        $related = $model->{$relationship};

        return $related instanceof Model
            ? $related
            : null;
    }

    private function displayName(
        ?Model $model
    ): ?string {
        if ($model === null) {
            return null;
        }

        foreach (
            [
                'name',
                'legal_name',
                'full_name',
                'title',
            ]
            as $attribute
        ) {
            $value = $model->getAttribute(
                $attribute
            );

            if (
                is_string($value)
                && trim($value) !== ''
            ) {
                return trim($value);
            }
        }

        return sprintf(
            '%s #%s',
            class_basename($model),
            $model->getKey()
        );
    }

    private function displayReference(
        ?Model $model
    ): ?string {
        if ($model === null) {
            return null;
        }

        foreach (
            [
                'reference',
                'reference_number',
                'number',
                'invoice_number',
                'lease_number',
                'code',
                'name',
            ]
            as $attribute
        ) {
            $value = $model->getAttribute(
                $attribute
            );

            if (
                (is_string($value) || is_numeric($value))
                && trim((string) $value) !== ''
            ) {
                return trim(
                    (string) $value
                );
            }
        }

        return sprintf(
            '%s #%s',
            class_basename($model),
            $model->getKey()
        );
    }
}
