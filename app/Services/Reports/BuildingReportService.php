<?php

namespace App\Services\Reports;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use Carbon\Carbon;
use RuntimeException;

/**
 * Operational and financial report for one Building.
 */
class BuildingReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(
        Building $building,
        ?string $from = null,
        ?string $to = null
    ): array {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $unitIds = $building->units()
            ->pluck('id');

        $leaseIds = Lease::query()
            ->whereIn('unit_id', $unitIds)
            ->pluck('id');

        $invoiceQuery = Invoice::query()
            ->whereIn('lease_id', $leaseIds)
            ->where('status', '!=', 'cancelled');

        $this->applyDateRange(
            $invoiceQuery,
            'issue_date',
            $fromDate,
            $toDate
        );

        $invoices = $invoiceQuery->get();

        $paymentQuery = Payment::query()
            ->whereIn('lease_id', $leaseIds);

        $this->applyDateRange(
            $paymentQuery,
            'payment_date',
            $fromDate,
            $toDate
        );

        $payments = $paymentQuery->get();

        $expenseQuery = OwnerExpense::query()
            ->where('building_id', $building->id);

        $this->applyDateRange(
            $expenseQuery,
            'expense_date',
            $fromDate,
            $toDate
        );

        $expenses = $expenseQuery
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $ownerLedgerQuery = OwnerTransaction::query()
            ->where('building_id', $building->id);

        $this->applyDateRange(
            $ownerLedgerQuery,
            'transaction_date',
            $fromDate,
            $toDate
        );

        $ownerTransactions = $ownerLedgerQuery->get();

        $building->load([
            'ownerships.party',
            'units',
        ]);

        return [
            'building' => [
                'id' => $building->id,
                'name' => $building->name,
                'address' => $building->address,
                'location' => $building->location,
            ],

            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'summary' => [
                'units' => $building->units->count(),
                'leases' => $leaseIds->count(),

                /*
     * Preserve the existing generic totals as the complete Building
     * receivable position for backward compatibility.
     */
                'invoiced' => (int) $invoices->sum('total_amount'),

                'invoice_settled' => (int) $invoices->sum(
                    fn (Invoice $invoice): int => $invoice->paidAmount()
                ),

                /*
     * V1.0.1 explicitly separates contractual rent from Security Deposit
     * close-out debt.
     */
                'rent_invoiced' => (int) $invoices
                    ->where('type', 'rent')
                    ->sum('total_amount'),

                'security_deposit_debt_invoiced' => (int) $invoices
                    ->where('type', 'security_deposit_debt')
                    ->sum('total_amount'),

                'rent_outstanding' => (int) $invoices
                    ->where('type', 'rent')
                    ->sum(
                        fn (Invoice $invoice): int => $invoice->outstandingAmount()
                    ),

                'security_deposit_debt_outstanding' => (int) $invoices
                    ->where('type', 'security_deposit_debt')
                    ->sum(
                        fn (Invoice $invoice): int => $invoice->outstandingAmount()
                    ),

                'total_outstanding' => (int) $invoices->sum(
                    fn (Invoice $invoice): int => $invoice->outstandingAmount()
                ),

                'cash_received' => (int) $payments->sum('amount'),

                'property_expenses' => (int) $expenses->sum('amount'),

                'owner_rent_entitlement' => $this->ledgerCategory(
                    $ownerTransactions,
                    'rent_entitlement',
                    'credit'
                ),

                'management_fees' => $this->ledgerCategory(
                    $ownerTransactions,
                    'management_fee',
                    'debit'
                ),

                'agent_commissions' => $this->ledgerCategory(
                    $ownerTransactions,
                    'agent_commission',
                    'debit'
                ),
            ],

            'ownership' => $building->ownerships
                ->map(fn ($ownership): array => [
                'party_id' => $ownership->party_id,
                'owner' => $ownership->party->name
                    ?? $ownership->party->legal_name,
                'percentage' => $ownership->ownership_percentage,
                ])
                ->values()
                ->all(),

            'expenses' => $expenses
                ->map(fn (OwnerExpense $expense): array => [
                'id' => $expense->id,
                'date' => $expense->expense_date->toDateString(),
                'description' => $expense->description,
                'amount' => $expense->amount,
                'unit_id' => $expense->unit_id,
                'reference' => $expense->reference,
                ])
                ->values()
                ->all(),
        ];
    }

    private function ledgerCategory(
        $transactions,
        string $category,
        string $direction
    ): int {
        return (int) $transactions
            ->where('category', $category)
            ->where('direction', $direction)
            ->sum('amount');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $value
            )->startOfDay();
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
}
