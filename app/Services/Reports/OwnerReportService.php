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
                    'agent_commissions' => 0,
                    'expenses' => 0,
                    'payouts' => 0,
                    'adjustments_credit' => 0,
                    'adjustments_debit' => 0,
                ],
                'transactions' => [],
            ];
        }

        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $openingBalance = 0;

        if ($fromDate !== null) {
            $openingCredits = OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'credit')
                ->whereDate('transaction_date', '<', $fromDate)
                ->sum('amount');

            $openingDebits = OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'debit')
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

        $credits = (int) $transactions
            ->where('direction', 'credit')
            ->sum('amount');

        $debits = (int) $transactions
            ->where('direction', 'debit')
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
            ],

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
