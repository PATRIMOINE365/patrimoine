<?php

namespace App\Services\Reports;

use App\Models\OwnerTransaction;
use App\Models\Party;
use Carbon\Carbon;
use RuntimeException;

/**
 * Financial statement for one property owner.
 *
 * Owner accounting is derived exclusively from OwnerTransaction records.
 * This keeps reporting consistent with the authoritative owner ledger.
 */
class OwnerReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(
        Party $owner,
        ?string $from = null,
        ?string $to = null
    ): array {
        $account = $owner->ownerAccount;

        if ($account === null) {
            return [
                'owner' => $this->partySummary($owner),
                'period' => $this->period($from, $to),
                'summary' => [
                    'opening_balance' => 0,
                    'credits' => 0,
                    'debits' => 0,
                    'closing_balance' => 0,
                    'rent_entitlement' => 0,
                    'owner_deposits' => 0,
                    'management_fees' => 0,
                    'management_fee_vat' => 0,
                    'agent_commissions' => 0,
                    'expenses' => 0,
                    'payouts' => 0,
                    'adjustments_credit' => 0,
                    'adjustments_debit' => 0,
                    'reserve_transfers_credit' => 0,
                    'reserve_transfers_debit' => 0,
                ],
                'last_payout_date' => null,
                'tenants' => [],
                'transactions' => [],
            ];
        }

        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $openingBalance = 0;

        if ($fromDate !== null) {
            /*
             * V1.0.48 (audit finding 3): internal reserve transfers are
             * excluded here. A transfer moves money between the owner's
             * own two pools — one ledger row, no matching opposite —
             * so counting it into a consolidated opening balance
             * invented money that did not exist. The statement still
             * SHOWS transfers, on their own lines below, with zero
             * effect on the arithmetic.
             */
            $openingCredits = OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'credit')
                ->where('category', '<>', 'reserve_transfer')
                ->whereDate('transaction_date', '<', $fromDate)
                ->sum('amount');

            $openingDebits = OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'debit')
                ->where('category', '<>', 'reserve_transfer')
                ->whereDate('transaction_date', '<', $fromDate)
                ->sum('amount');

            $openingBalance =
                (int) $openingCredits - (int) $openingDebits;
        }

        $query = OwnerTransaction::query()
            ->where('owner_account_id', $account->id)
            ->with([
                'building:id,name',
                'unit:id,name',
                'invoice:id,invoice_number',
            ]);

        $this->applyDateRange(
            $query,
            'transaction_date',
            $fromDate,
            $toDate
        );

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        /*
         * V1.0.48 (audit finding 3): consolidated income and expenditure
         * exclude internal reserve transfers — a transfer is the owner's
         * own money changing pockets, not money received or spent. The
         * reserve_transfers_credit/debit lines below still show them.
         */
        $credits = (int) $transactions
            ->where('direction', 'credit')
            ->where('category', '!=', 'reserve_transfer')
            ->sum('amount');

        $debits = (int) $transactions
            ->where('direction', 'debit')
            ->where('category', '!=', 'reserve_transfer')
            ->sum('amount');

        return [
            'owner' => $this->partySummary($owner),

            'period' => $this->period($from, $to),

            'summary' => [
                'opening_balance' => $openingBalance,
                'credits' => $credits,
                'debits' => $debits,
                'closing_balance' => $openingBalance + $credits - $debits,

                'rent_entitlement' => $this->categoryTotal(
                    $transactions,
                    'rent_entitlement',
                    'credit'
                ),

                'owner_deposits' => $this->categoryTotal(
                    $transactions,
                    'owner_deposit',
                    'credit'
                ),

                'management_fees' => $this->categoryTotal(
                    $transactions,
                    'management_fee',
                    'debit'
                ),

                /*
                 * VAT is charged on the management fee and billed to the
                 * owner, so it belongs on its own line: an owner checking
                 * the statement needs to see the fee and the tax on it
                 * separately, not one blended deduction.
                 */
                'management_fee_vat' => $this->categoryTotal(
                    $transactions,
                    'management_fee_vat',
                    'debit'
                ),

                'agent_commissions' => $this->categoryTotal(
                    $transactions,
                    'agent_commission',
                    'debit'
                ),

                'expenses' => $this->categoryTotal(
                    $transactions,
                    'expense',
                    'debit'
                ),

                'payouts' => $this->categoryTotal(
                    $transactions,
                    'payout',
                    'debit'
                ),

                'adjustments_credit' => $this->categoryTotal(
                    $transactions,
                    'adjustment',
                    'credit'
                ),

                'adjustments_debit' => $this->categoryTotal(
                    $transactions,
                    'adjustment',
                    'debit'
                ),

                'reserve_transfers_credit' => $this->categoryTotal(
                    $transactions,
                    'reserve_transfer',
                    'credit'
                ),

                'reserve_transfers_debit' => $this->categoryTotal(
                    $transactions,
                    'reserve_transfer',
                    'debit'
                ),
            ],

            /*
             * When the owner last took money. The console pre-fills the
             * statement period from the day after this, because "since I
             * last collected" is the period an owner actually asks about.
             */
            'last_payout_date' => $this->lastPayoutDate($account->id),

            'tenants' => $this->tenantRents(
                $owner,
                $account->id,
                $fromDate,
                $toDate
            ),

            'transactions' => $transactions
                ->map(fn (OwnerTransaction $transaction): array => [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date->toDateString(),
                'direction' => $transaction->direction,
                'category' => $transaction->category,
                'amount' => $transaction->amount,
                'building' => $transaction->building?->name,
                'unit' => $transaction->unit?->name,
                'invoice' => $transaction->invoice?->invoice_number,
                'reference' => $transaction->reference,
                'notes' => $transaction->notes,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The date of the owner's most recent payout, if any.
     */
    private function lastPayoutDate(int $accountId): ?string
    {
        $date = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('category', 'payout')
            ->where('direction', 'debit')
            ->max('transaction_date');

        return $date === null
            ? null
            : Carbon::parse($date)->toDateString();
    }

    /**
     * Rent collected in the period, tenant by tenant.
     *
     * The ledger alone cannot answer "who paid, and who did not": a tenant
     * who paid nothing produces no transaction and would simply be absent.
     * So the leases on the owner's buildings are listed first, and the
     * collected figure is attached to each -- a tenant in arrears shows as
     * zero rather than vanishing from the statement.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tenantRents(
        Party $owner,
        int $accountId,
        ?Carbon $from,
        ?Carbon $to
    ): array {
        $ownedBuildingIds = \App\Models\BuildingOwner::query()
            ->where('party_id', $owner->id)
            ->pluck('building_id')
            ->all();

        if ($ownedBuildingIds === []) {
            return [];
        }

        $leaseQuery = \App\Models\Lease::query()
            ->with(['tenant:id,name,legal_name', 'unit:id,name,building_id', 'unit.building:id,name'])
            ->whereHas(
                'unit',
                fn ($query) => $query->whereIn('building_id', $ownedBuildingIds)
            );

        /*
         * A lease belongs on the statement when it overlapped the period.
         * An open-ended lease has no end date and therefore always
         * overlaps anything on or after its start.
         */
        if ($to !== null) {
            $leaseQuery->whereDate('start_date', '<=', $to->toDateString());
        }

        if ($from !== null) {
            $leaseQuery->where(function ($query) use ($from): void {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $from->toDateString());
            });
        }

        $leases = $leaseQuery->orderBy('id')->get();

        /*
         * Columns are qualified because this query is joined to invoices
         * below, and both tables carry a lease_id.
         */
        $collectedQuery = OwnerTransaction::query()
            ->where('owner_transactions.owner_account_id', $accountId)
            ->where('owner_transactions.category', 'rent_entitlement')
            ->where('owner_transactions.direction', 'credit')
            ->whereNotNull('owner_transactions.lease_id');

        $this->applyDateRange(
            $collectedQuery,
            'owner_transactions.transaction_date',
            $from,
            $to
        );

        /*
         * One row per rent period actually settled.
         *
         * A period the tenant has not paid produces no cash, so it is
         * simply absent -- the owner is only ever shown money that is
         * ready to be handed over. When that rent is paid later it appears
         * on the next statement, still carrying the period it belongs to
         * rather than the month it happened to arrive.
         *
         * Grouping by Invoice rather than taking a min/max span keeps a
         * gap honest: paying January and March lists two rows, not one row
         * implying January through March.
         */
        $totalPerLease = (clone $collectedQuery)
            ->selectRaw(
                'owner_transactions.lease_id as lease_id,'
                .' SUM(owner_transactions.amount) as total'
            )
            ->groupBy('owner_transactions.lease_id')
            ->pluck('total', 'lease_id');

        $settled = (clone $collectedQuery)
            ->join(
                'invoices',
                'invoices.id',
                '=',
                'owner_transactions.invoice_id'
            )
            ->selectRaw(
                'owner_transactions.lease_id as lease_id,'
                .' invoices.period_start as period_start,'
                .' invoices.period_end as period_end,'
                .' SUM(owner_transactions.amount) as total'
            )
            ->groupBy(
                'owner_transactions.lease_id',
                'invoices.period_start',
                'invoices.period_end'
            )
            ->orderBy('invoices.period_start')
            ->get()
            ->groupBy('lease_id');

        /*
         * A lease that produced money in the period must appear even if
         * the building has since changed hands.
         */
        $missingIds = array_diff(
            $totalPerLease->keys()->map(fn ($id) => (int) $id)->all(),
            $leases->pluck('id')->all()
        );

        if ($missingIds !== []) {
            $leases = $leases->merge(
                \App\Models\Lease::query()
                    ->with(['tenant:id,name,legal_name', 'unit:id,name,building_id', 'unit.building:id,name'])
                    ->whereIn('id', $missingIds)
                    ->get()
            );
        }

        $rows = [];

        foreach ($leases->sortBy('id') as $lease) {
            $base = [
                'tenant' => $lease->tenant?->name
                    ?? $lease->tenant?->legal_name,

                'property' => trim(
                    ($lease->unit?->building?->name ?? '')
                    .' '
                    .($lease->unit?->name ?? '')
                ),

                'lease_status' => $lease->status,

                'monthly_rent' => (int) $lease->rent_amount,

                'management_fee_rate' => $this->feeRate($lease),
            ];

            $periods = $settled[$lease->id] ?? collect();

            foreach ($periods as $period) {
                $rows[] = $base + [
                    'rent_from_date' => Carbon::parse(
                        $period->period_start
                    )->toDateString(),

                    'rent_to_date' => Carbon::parse(
                        $period->period_end
                    )->toDateString(),

                    'rent_collected' => (int) $period->total,
                ];
            }

            /*
             * Two cases share one row, both carrying no rent period.
             *
             * A tenant who paid nothing: the unit is let and produced no
             * money, which an owner needs to see rather than have the
             * tenant silently absent.
             *
             * Entitlement that carries no Invoice -- an opening balance or
             * a manual correction -- cannot be attributed to a period, but
             * it still has to appear or the tenant rows would total less
             * than the rent figure in the summary and the statement would
             * not add up.
             */
            $unattributed =
                (int) ($totalPerLease[$lease->id] ?? 0)
                - (int) $periods->sum('total');

            if ($periods->isEmpty() || $unattributed !== 0) {
                $rows[] = $base + [
                    'rent_from_date' => null,
                    'rent_to_date' => null,
                    'rent_collected' => $unattributed,
                ];
            }
        }

        return $rows;
    }

    /**
     * The lease's management fee expressed for a reader, not a machine.
     */
    private function feeRate($lease): string
    {
        return match ($lease->management_fee_type) {
            'percentage' => rtrim(
                rtrim(
                    number_format(
                        (float) $lease->management_fee_value,
                        2,
                        '.',
                        ''
                    ),
                    '0'
                ),
                '.'
            ).'%',

            'fixed' => (string) (int) $lease->management_fee_value,

            default => '—',
        };
    }

    private function categoryTotal(
        $transactions,
        string $category,
        string $direction
    ): int {
        return (int) $transactions
            ->where('category', $category)
            ->where('direction', $direction)
            ->sum('amount');
    }

    /**
     * @return array<string, mixed>
     */
    private function partySummary(Party $party): array
    {
        return [
            'id' => $party->id,
            'type' => $party->type,
            'name' => $party->name ?? $party->legal_name,
            'email' => $party->email,
            'phone' => $party->phone,
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)
                ->startOfDay();
        } catch (\Throwable) {
            throw new RuntimeException(
                'Report dates must use YYYY-MM-DD format.'
            );
        }
    }

    private function applyDateRange(
        $query,
        string $column,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        if ($from !== null) {
            $query->whereDate(
                $column,
                '>=',
                $from->toDateString()
            );
        }

        if ($to !== null) {
            $query->whereDate(
                $column,
                '<=',
                $to->toDateString()
            );
        }
    }

    /**
     * @return array<string, ?string>
     */
    private function period(
        ?string $from,
        ?string $to
    ): array {
        return [
            'from' => $from,
            'to' => $to,
        ];
    }
}
