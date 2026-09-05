<?php

namespace App\Services\LeaseDeletion;

use App\Models\Lease;
use App\Services\Accounting\AccountingRuntimeGate;
use App\Support\OrganisationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10B1
 *
 * Read-only discovery/calculation service for destructive Lease deletion.
 *
 * IMPORTANT:
 * - This service MUST NOT mutate operational state.
 * - This service MUST NOT delete anything.
 * - This service MUST NOT create Journal entries.
 * - This service MUST fail closed when an affected dependency cannot be
 *   attributed deterministically.
 */
class LeaseDeletionImpactService
{
    public function __construct(
        private readonly AccountingRuntimeGate $accountingRuntime
    ) {
    }

    /**
     * Calculate the complete known operational/accounting impact of deleting
     * the supplied Lease.
     *
     * @return array<string, mixed>
     */
    public function calculate(Lease $lease): array
    {
        $lease->loadMissing([
            'tenant',
            'unit.building',
        ]);

        $blockingReasons = [];
        $unknownDependencies = [];

        /*
         * Destructive deletion has different accounting implications before
         * and after the controlled V1.0.5 opening-balance cutover.
         *
         * Before cutover there are no runtime V1.0.5 Journal postings to
         * reverse. After cutover every affected financial Journal posting
         * must be attributable and reversibly neutralized before deletion
         * can ever be considered safe.
         */
        $accountingRuntimeEnabled =
            $this->accountingRuntime->enabled();

        /*
         * Direct Lease-owned records.
         *
         * These are discovered from the database schema instead of assuming
         * that every possible V1.0.5 installation necessarily contains every
         * optional table.
         */
        $directTables = [
            'invoices',
            'lease_term_versions',
            'owner_transactions',
            'payments',
            'rent_increments',
            'security_deposit_applications',
            'security_deposit_deductions',
            'security_deposit_settlements',
            'tenant_fund_accounts',
            'withdrawal_receipts',
        ];

        $direct = [];

        foreach ($directTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'lease_id')) {
                $unknownDependencies[] = [
                    'type' => 'schema_mismatch',
                    'table' => $table,
                    'reason' => 'Expected Lease-owned table does not contain lease_id.',
                ];

                continue;
            }

            $query = DB::table($table)
                ->where('lease_id', $lease->id);

            $direct[$table] = [
                'count' => (clone $query)->count(),
                'ids' => (clone $query)
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
            ];
        }

        $invoiceIds = $direct['invoices']['ids'] ?? [];
        $paymentIds = $direct['payments']['ids'] ?? [];
        $tenantFundAccountIds =
            $direct['tenant_fund_accounts']['ids'] ?? [];
        $ownerTransactionIds =
            $direct['owner_transactions']['ids'] ?? [];

        /*
         * Payment allocations are indirect dependencies.
         */
        $paymentAllocations = $this->paymentAllocations(
            $lease->id,
            $paymentIds,
            $invoiceIds,
            $unknownDependencies
        );

        /*
         * Tenant fund transactions belong to Lease-specific Tenant Fund
         * Accounts rather than necessarily carrying lease_id themselves.
         */
        $tenantFundTransactions = $this->tenantFundTransactions(
            $tenantFundAccountIds,
            $unknownDependencies
        );

        /*
         * Owner payout allocations are especially important because an Owner
         * payout may contain credits from multiple Leases. We must expose
         * those entanglements rather than assuming the whole payout belongs
         * to this Lease.
         */
        $ownerPayoutAllocations = $this->ownerPayoutAllocations(
            $ownerTransactionIds,
            $unknownDependencies
        );

        foreach (
            $ownerPayoutAllocations['shared_payouts'] ?? []
            as $sharedPayout
        ) {
            $blockingReasons[] = sprintf(
                'Owner payout %s contains allocations both inside and outside Lease %d.',
                $sharedPayout['owner_payout_id'],
                $lease->id
            );
        }

        /*
         * Journal discovery remains read-only.
         *
         * We deliberately classify entries rather than reversing anything.
         */
        $journal = $this->journalImpact(
            $lease,
            $direct,
            $paymentAllocations,
            $tenantFundTransactions,
            $unknownDependencies
        );

        /*
         * Once runtime accounting is active, destructive deletion may proceed
         * only when every affected Journal entry is deterministically
         * attributable and therefore capable of being neutralized safely.
         *
         * The impact calculator does not perform reversals. It merely proves
         * whether the future destructive service has a complete accounting
         * set to work from.
         */
        if ($accountingRuntimeEnabled) {
            foreach (
                $journal['unclassified_entries'] ?? []
                as $entry
            ) {
                $blockingReasons[] = sprintf(
                    'Journal entry %s cannot be attributed deterministically to Lease %d.',
                    $entry['journal_number']
                        ?? $entry['id']
                        ?? 'unknown',
                    $lease->id
                );
            }

            foreach (
                $journal['shared_entries'] ?? []
                as $entry
            ) {
                $blockingReasons[] = sprintf(
                    'Journal entry %s contains financial effects shared with records outside Lease %d.',
                    $entry['journal_number']
                        ?? $entry['id']
                        ?? 'unknown',
                    $lease->id
                );
            }
        }

        /*
         * Opening-balance accounting needs special treatment.
         *
         * Lease-specific opening entries may eventually be neutralized
         * deterministically. Consolidated Owner opening positions must never
         * be blindly reversed as a whole.
         */
        foreach ($journal['opening_balance_entries'] as $entry) {
            if (
                ($entry['source_type'] ?? null) === 'owner_account'
                || ($entry['classification'] ?? null)
                    === 'consolidated_owner_opening_balance'
            ) {
                $blockingReasons[] =
                    'Lease impact intersects a consolidated Owner opening balance and requires attributable partial-neutralization logic.';
            }
        }

        foreach ($unknownDependencies as $dependency) {
            $blockingReasons[] =
                $dependency['reason']
                ?? 'An affected dependency could not be classified safely.';
        }

        $blockingReasons = array_values(
            array_unique($blockingReasons)
        );

        return [
            'lease' => [
                'id' => $lease->id,
                'reference' =>
                    $lease->reference
                    ?? $lease->lease_reference
                    ?? null,
                'status' => $lease->status,
                'tenant' => [
                    'id' => $lease->tenant?->id,
                    'name' =>
                        $lease->tenant?->name
                        ?? $lease->tenant?->display_name
                        ?? null,
                ],
                'unit' => [
                    'id' => $lease->unit?->id,
                    'name' =>
                        $lease->unit?->name
                        ?? $lease->unit?->unit_number
                        ?? null,
                ],
                'building' => [
                    'id' => $lease->unit?->building?->id,
                    'name' =>
                        $lease->unit?->building?->name
                        ?? null,
                ],
            ],

            'direct_dependencies' => $direct,

            'indirect_dependencies' => [
                'payment_allocations' =>
                    $paymentAllocations,

                'tenant_fund_transactions' =>
                    $tenantFundTransactions,

                'owner_payout_allocations' =>
                    $ownerPayoutAllocations,
            ],

            'journal' => $journal,

            'accounting' => [
                'runtime_enabled' =>
                    $accountingRuntimeEnabled,

                /*
                 * This flag means only that accounting classification did not
                 * itself discover a blocker. It does NOT authorize deletion;
                 * safe_to_delete remains the aggregate fail-closed result.
                 */
                'impact_classified' =>
                    $accountingRuntimeEnabled
                        ? (
                            ($journal['unclassified_entries'] ?? []) === []
                            && ($journal['shared_entries'] ?? []) === []
                        )
                        : true,
            ],

            'unknown_dependencies' =>
                $unknownDependencies,

            'blocking_reasons' =>
                $blockingReasons,

            /*
             * Fail closed.
             */
            'safe_to_delete' =>
                $blockingReasons === []
                && $unknownDependencies === [],
        ];
    }

    /**
     * @param array<int> $paymentIds
     * @param array<int> $invoiceIds
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function paymentAllocations(
        int $leaseId,
        array $paymentIds,
        array $invoiceIds,
        array &$unknown
    ): array {
        if (! Schema::hasTable('payment_allocations')) {
            return [
                'count' => 0,
                'ids' => [],
            ];
        }

        $hasPaymentId =
            Schema::hasColumn(
                'payment_allocations',
                'payment_id'
            );

        $hasInvoiceId =
            Schema::hasColumn(
                'payment_allocations',
                'invoice_id'
            );

        if (! $hasPaymentId && ! $hasInvoiceId) {
            $unknown[] = [
                'type' => 'payment_allocation_schema',
                'reason' =>
                    'payment_allocations cannot be attributed by payment_id or invoice_id.',
            ];

            return [
                'count' => 0,
                'ids' => [],
            ];
        }

        /*
         * V1.0.50: nothing to look for means nothing found.
         *
         * A lease that has never been paid or invoiced has no payment
         * ids and no invoice ids. The closure below then added no
         * constraint at all, so the raw query returned EVERY allocation
         * on the installation — other leases', other organisations' —
         * and each one read as "crossing lease boundaries". Every unpaid
         * draft was undeletable, and its impact preview listed
         * allocation ids that belonged to somebody else.
         */
        $paymentIds = $hasPaymentId ? $paymentIds : [];
        $invoiceIds = $hasInvoiceId ? $invoiceIds : [];

        if ($paymentIds === [] && $invoiceIds === []) {
            return [
                'count' => 0,
                'ids' => [],
                'cross_lease' => [],
            ];
        }

        $query =
            DB::table('payment_allocations')
                ->where(function ($query) use (
                    $paymentIds,
                    $invoiceIds,
                    $hasPaymentId,
                    $hasInvoiceId
                ): void {
                    $used = false;

                    if ($hasPaymentId && $paymentIds !== []) {
                        $query->whereIn(
                            'payment_id',
                            $paymentIds
                        );

                        $used = true;
                    }

                    if ($hasInvoiceId && $invoiceIds !== []) {
                        if ($used) {
                            $query->orWhereIn(
                                'invoice_id',
                                $invoiceIds
                            );
                        } else {
                            $query->whereIn(
                                'invoice_id',
                                $invoiceIds
                            );
                        }
                    }
                });

        /*
         * DB::table() bypasses OrganisationScope, so the boundary the
         * scope would have drawn is drawn here by hand.
         */
        if (
            OrganisationContext::bound()
            && Schema::hasColumn('payment_allocations', 'organisation_id')
        ) {
            $query->where(
                'organisation_id',
                OrganisationContext::id()
            );
        }

        $rows =
            $query
                ->orderBy('id')
                ->get();

        /*
         * Detect cross-Lease allocation contamination.
         */
        $crossLease = [];

        if (
            $hasPaymentId
            && $hasInvoiceId
            && $rows->isNotEmpty()
        ) {
            foreach ($rows as $row) {
                $paymentLeaseId =
                    isset($row->payment_id)
                    ? DB::table('payments')
                        ->where('id', $row->payment_id)
                        ->value('lease_id')
                    : null;

                $invoiceLeaseId =
                    isset($row->invoice_id)
                    ? DB::table('invoices')
                        ->where('id', $row->invoice_id)
                        ->value('lease_id')
                    : null;

                if (
                    $paymentLeaseId !== null
                    && $invoiceLeaseId !== null
                    && (
                        (int) $paymentLeaseId !== $leaseId
                        || (int) $invoiceLeaseId !== $leaseId
                    )
                ) {
                    $crossLease[] = [
                        'allocation_id' => (int) $row->id,
                        'payment_lease_id' =>
                            (int) $paymentLeaseId,
                        'invoice_lease_id' =>
                            (int) $invoiceLeaseId,
                    ];
                }
            }
        }

        if ($crossLease !== []) {
            $unknown[] = [
                'type' =>
                    'cross_lease_payment_allocation',
                'reason' =>
                    'One or more payment allocations cross Lease boundaries.',
                'records' =>
                    $crossLease,
            ];
        }

        return [
            'count' => $rows->count(),
            'ids' => $rows
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            'cross_lease' => $crossLease,
        ];
    }

    /**
     * @param array<int> $accountIds
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function tenantFundTransactions(
        array $accountIds,
        array &$unknown
    ): array {
        if (! Schema::hasTable('tenant_fund_transactions')) {
            return [
                'count' => 0,
                'ids' => [],
            ];
        }

        if (
            ! Schema::hasColumn(
                'tenant_fund_transactions',
                'tenant_fund_account_id'
            )
        ) {
            $unknown[] = [
                'type' => 'tenant_fund_schema',
                'reason' =>
                    'tenant_fund_transactions cannot be attributed to Tenant Fund Accounts.',
            ];

            return [
                'count' => 0,
                'ids' => [],
            ];
        }

        if ($accountIds === []) {
            return [
                'count' => 0,
                'ids' => [],
            ];
        }

        $rows =
            DB::table('tenant_fund_transactions')
                ->whereIn(
                    'tenant_fund_account_id',
                    $accountIds
                )
                ->orderBy('id')
                ->get();

        return [
            'count' => $rows->count(),
            'ids' => $rows
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }

    /**
     * @param array<int> $ownerTransactionIds
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function ownerPayoutAllocations(
        array $ownerTransactionIds,
        array &$unknown
    ): array {
        if (
            ! Schema::hasTable(
                'owner_payout_allocations'
            )
        ) {
            return [
                'count' => 0,
                'ids' => [],
                'shared_payouts' => [],
            ];
        }

        if (
            ! Schema::hasColumn(
                'owner_payout_allocations',
                'owner_transaction_id'
            )
        ) {
            $unknown[] = [
                'type' =>
                    'owner_payout_allocation_schema',
                'reason' =>
                    'Owner payout allocations cannot be attributed to Owner Transactions.',
            ];

            return [
                'count' => 0,
                'ids' => [],
                'shared_payouts' => [],
            ];
        }

        if ($ownerTransactionIds === []) {
            return [
                'count' => 0,
                'ids' => [],
                'shared_payouts' => [],
            ];
        }

        $rows =
            DB::table('owner_payout_allocations')
                ->whereIn(
                    'owner_transaction_id',
                    $ownerTransactionIds
                )
                ->orderBy('id')
                ->get();

        $sharedPayouts = [];

        if (
            Schema::hasColumn(
                'owner_payout_allocations',
                'owner_payout_id'
            )
        ) {
            foreach (
                $rows
                    ->pluck('owner_payout_id')
                    ->filter()
                    ->unique()
                as $payoutId
            ) {
                $allAllocationTransactionIds =
                    DB::table(
                        'owner_payout_allocations'
                    )
                        ->where(
                            'owner_payout_id',
                            $payoutId
                        )
                        ->pluck(
                            'owner_transaction_id'
                        )
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->all();

                $outside =
                    array_values(
                        array_diff(
                            $allAllocationTransactionIds,
                            $ownerTransactionIds
                        )
                    );

                if ($outside !== []) {
                    $sharedPayouts[] = [
                        'owner_payout_id' =>
                            (int) $payoutId,

                        'lease_owner_transaction_ids' =>
                            array_values(
                                array_intersect(
                                    $allAllocationTransactionIds,
                                    $ownerTransactionIds
                                )
                            ),

                        'outside_owner_transaction_ids' =>
                            $outside,
                    ];
                }
            }
        }

        return [
            'count' => $rows->count(),
            'ids' => $rows
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            'shared_payouts' => $sharedPayouts,
        ];
    }

    /**
     * Phase 10B1 Journal discovery.
     *
     * No Journal mutation occurs here.
     *
     * @param array<string, mixed> $direct
     * @param array<string, mixed> $paymentAllocations
     * @param array<string, mixed> $tenantFundTransactions
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function journalImpact(
        Lease $lease,
        array $direct,
        array $paymentAllocations,
        array $tenantFundTransactions,
        array &$unknown
    ): array {
        $table = null;

        foreach (
            ['journal_entries', 'journal_transactions']
            as $candidate
        ) {
            if (Schema::hasTable($candidate)) {
                $table = $candidate;
                break;
            }
        }

        if ($table === null) {
            $unknown[] = [
                'type' => 'journal_schema',
                'reason' =>
                    'Financial Journal header table could not be identified.',
            ];

            return [
                'entries' => [],
                'active_entries' => [],
                'already_reversed_entries' => [],
                'shared_entries' => [],
                'unclassified_entries' => [],
                'opening_balance_entries' => [],
            ];
        }

        /*
         * Source-pair inventory for known Lease-owned financial records.
         *
         * 10B1 intentionally does not claim that every matching source must
         * be reversed. That classification belongs to later accounting work.
         */
        $sourceIds = [];

        foreach ($direct as $tableName => $info) {
            foreach ($info['ids'] ?? [] as $id) {
                $sourceIds[] = [
                    'table' => $tableName,
                    'id' => (int) $id,
                ];
            }
        }

        /*
         * Payment allocations are Lease-owned even though they carry no
         * lease_id of their own: they are reached through this Lease's
         * Payments and Invoices, and paymentAllocations() has already
         * refused to continue when an allocation crosses a Lease boundary.
         *
         * The rent-receipt, owner-entitlement and management-fee postings
         * all record PaymentAllocation as their structured source, so
         * omitting allocations here left every Lease that had ever
         * received a rent payment permanently undeletable.
         */
        foreach (
            $paymentAllocations['ids'] ?? []
            as $id
        ) {
            $sourceIds[] = [
                'table' => 'payment_allocations',
                'id' => (int) $id,
            ];
        }

        foreach (
            $tenantFundTransactions['ids'] ?? []
            as $id
        ) {
            $sourceIds[] = [
                'table' =>
                    'tenant_fund_transactions',
                'id' => (int) $id,
            ];
        }

        $entries = collect();

        /*
         * Journal discovery must never reach outside the Lease's own
         * Organisation. Lease ids are globally unique, but snapshot text
         * matching is not id-aware, so the tenant boundary is enforced on
         * every Journal query rather than assumed.
         */
        $organisationId =
            Schema::hasColumn($table, 'organisation_id')
                ? $lease->organisation_id
                : null;

        $scoped = function () use ($table, $organisationId) {
            $query = DB::table($table);

            if ($organisationId !== null) {
                $query->where(
                    'organisation_id',
                    $organisationId
                );
            }

            return $query;
        };

        /*
         * Prefer structured source references when available.
         */
        if (
            Schema::hasColumn($table, 'source_type')
            && Schema::hasColumn($table, 'source_id')
        ) {
            foreach ($sourceIds as $source) {
                $types =
                    $this->possibleSourceTypes(
                        $source['table']
                    );

                foreach ($types as $type) {
                    $entries =
                        $entries->merge(
                            $scoped()
                                ->where(
                                    'source_type',
                                    $type
                                )
                                ->where(
                                    'source_id',
                                    $source['id']
                                )
                                ->get()
                        );
                }
            }
        }

        /*
         * Also discover entries whose frozen snapshot explicitly identifies
         * this Lease. Snapshot matching is discovery evidence only; it is NOT
         * sufficient by itself to authorize a reversal.
         */
        foreach (
            [
                'snapshot',
                'context_snapshot',
                'source_snapshot',
                'metadata',
            ]
            as $column
        ) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            /*
             * The LIKE is only a cheap prefilter: '"lease_id":1' also
             * matches lease 10, 19 and 100, which would drag another
             * Lease's postings into this impact set and block deletion
             * forever. Every candidate is therefore re-checked by
             * decoding the frozen document and comparing the value.
             */
            $candidates =
                $scoped()
                    ->where(
                        $column,
                        'like',
                        '%"lease_id":'
                        . $lease->id
                        . '%'
                    )
                    ->get();

            $entries =
                $entries->merge(
                    $candidates->filter(
                        fn ($row) => $this->documentNamesLease(
                            ((array) $row)[$column] ?? null,
                            (int) $lease->id
                        )
                    )
                );
        }

        $entries =
            $entries
                ->unique('id')
                ->sortBy('id')
                ->values();

        $opening = [];
        $active = [];
        $alreadyReversed = [];
        $shared = [];
        $unclassified = [];

        /*
         * Structured source pairs are the authoritative evidence that a
         * Journal entry belongs exclusively to a Lease-owned operational
         * record. Snapshot matches remain discovery evidence only.
         */
        $structuredSourcePairs = [];

        foreach ($sourceIds as $source) {
            foreach (
                $this->possibleSourceTypes($source['table'])
                as $type
            ) {
                $structuredSourcePairs[] = [
                    'source_type' => $type,
                    'source_id' => (int) $source['id'],
                ];
            }
        }

        foreach ($entries as $entry) {
            $row = (array) $entry;

            $description =
                strtolower(
                    (string) (
                        $row['description']
                        ?? $row['memo']
                        ?? ''
                    )
                );

            $transactionType =
                strtolower(
                    (string) (
                        $row['transaction_type']
                        ?? $row['type']
                        ?? ''
                    )
                );

            $isOpening =
                str_contains(
                    $description,
                    'v1.0.5 opening balance'
                )
                || str_contains(
                    $transactionType,
                    'opening'
                );

            if ($isOpening) {
                $row['classification'] =
                    $this->classifyOpeningBalance(
                        $row
                    );

                $opening[] = $row;
                continue;
            }

            /*
             * Patrimoine's reversal foundation records:
             *
             * - reversed_by_id on the original entry;
             * - reversal_of_id on the reversal entry.
             *
             * Either relationship means this row must not be treated as an
             * unreversed financial effect requiring another reversal.
             */
            $isAlreadyReversed =
                ! empty($row['reversed_by_id'] ?? null)
                || ! empty($row['reversal_of_id'] ?? null);

            if ($isAlreadyReversed) {
                $row['classification'] =
                    'already_reversed';

                $alreadyReversed[] = $row;

                continue;
            }

            $hasStructuredAttribution = false;

            foreach ($structuredSourcePairs as $pair) {
                if (
                    (string) ($row['source_type'] ?? '')
                        === (string) $pair['source_type']
                    && (int) ($row['source_id'] ?? 0)
                        === (int) $pair['source_id']
                ) {
                    $hasStructuredAttribution = true;
                    break;
                }
            }

            /*
             * Owner payout accounting can aggregate value from multiple
             * Lease-owned Owner Transactions. It must never be assumed to be
             * Lease-exclusive merely because a frozen snapshot mentions this
             * Lease.
             */
            $sourceType =
                strtolower(
                    (string) ($row['source_type'] ?? '')
                );

            $transactionTypeLower =
                strtolower(
                    (string) (
                        $row['transaction_type']
                        ?? ''
                    )
                );

            $looksShared =
                str_contains($sourceType, 'ownerpayout')
                || str_contains($sourceType, 'owner_payout')
                || str_contains(
                    $transactionTypeLower,
                    'owner_payout'
                );

            if ($looksShared) {
                $row['classification'] =
                    'shared_financial_effect';

                $shared[] = $row;

                continue;
            }

            if (! $hasStructuredAttribution) {
                $row['classification'] =
                    'unclassified_snapshot_or_context_match';

                $unclassified[] = $row;

                continue;
            }

            $row['classification'] =
                'lease_exclusive_structured_source';

            $active[] = $row;
        }

        return [
            'table' => $table,

            'entries' =>
                $entries
                    ->map(fn ($row) => (array) $row)
                    ->all(),

            'active_entries' =>
                $active,

            'already_reversed_entries' =>
                $alreadyReversed,

            'shared_entries' =>
                $shared,

            'unclassified_entries' =>
                $unclassified,

            'opening_balance_entries' =>
                $opening,

            'warning' =>
                'Snapshot matches are discovery evidence only and must not independently authorize accounting reversal.',
        ];
    }

    /**
     * Decide whether a frozen Journal document really names this Lease.
     *
     * Snapshot discovery uses a substring LIKE, which cannot distinguish
     * lease 1 from lease 10. Decoding the document and comparing the value
     * makes the match exact and keeps one Lease's deletion from being
     * blocked by another Lease's postings.
     */
    private function documentNamesLease(
        mixed $document,
        int $leaseId
    ): bool {
        if (! is_string($document) || $document === '') {
            return false;
        }

        $decoded = json_decode($document, true);

        if (! is_array($decoded)) {
            /*
             * An unreadable document is not evidence of attribution. The
             * structured source pair remains the authoritative path, and
             * anything genuinely unattributable still fails closed.
             */
            return false;
        }

        return $this->arrayNamesLease($decoded, $leaseId);
    }

    /**
     * Recursively look for a lease_id equal to the supplied Lease.
     *
     * @param array<mixed> $document
     */
    private function arrayNamesLease(
        array $document,
        int $leaseId
    ): bool {
        foreach ($document as $key => $value) {
            if (
                $key === 'lease_id'
                && is_numeric($value)
                && (int) $value === $leaseId
            ) {
                return true;
            }

            if (
                is_array($value)
                && $this->arrayNamesLease($value, $leaseId)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function possibleSourceTypes(
        string $table
    ): array {
        $singular =
            rtrim($table, 's');

        $class =
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $singular
                    )
                )
            );

        return array_values(
            array_unique([
                $table,
                $singular,
                $class,
                'App\\Models\\' . $class,
            ])
        );
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function classifyOpeningBalance(
        array $entry
    ): string {
        $sourceType =
            strtolower(
                (string) (
                    $entry['source_type']
                    ?? ''
                )
            );

        if (
            str_contains(
                $sourceType,
                'owner'
            )
        ) {
            return
                'consolidated_owner_opening_balance';
        }

        /*
         * Opening-balance source_type may be stored using either:
         *
         * - snake_case identifiers such as tenant_fund_account; or
         * - model-class identifiers such as
         *   App\\Models\\TenantFundAccount.
         *
         * Normalize separators so both authoritative source conventions
         * classify identically.
         */
        $normalizedSourceType =
            str_replace(
                ['_', '-', '\\'],
                '',
                $sourceType
            );

        if (
            str_contains(
                $normalizedSourceType,
                'tenantfund'
            )
        ) {
            return
                'lease_specific_tenant_fund_opening_balance';
        }

        if (
            str_contains(
                $sourceType,
                'invoice'
            )
        ) {
            return
                'lease_specific_receivable_opening_balance';
        }

        return 'opening_balance_requires_review';
    }
}
