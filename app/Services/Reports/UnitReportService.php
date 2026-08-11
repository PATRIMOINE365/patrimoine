<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\OwnerExpense;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\Carbon;
use RuntimeException;

/**
 * Financial and tenancy history for one Unit.
 */
class UnitReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(
        Unit $unit,
        ?string $from = null,
        ?string $to = null
    ): array {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $unit->load([
            'building',
            'leases.tenant',
        ]);

        $leaseIds = $unit->leases
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

        $invoices = $invoiceQuery
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

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
            ->where('unit_id', $unit->id);

        $this->applyDateRange(
            $expenseQuery,
            'expense_date',
            $fromDate,
            $toDate
        );

        $expenses = $expenseQuery->get();

        return [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'description' => $unit->description,

                'building' => [
                    'id' => $unit->building->id,
                    'name' => $unit->building->name,
                ],
            ],

            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'summary' => [
                'leases' => $unit->leases->count(),

                'invoiced' =>
                    (int) $invoices->sum('total_amount'),

                'settled' =>
                    (int) $invoices->sum(
                        fn (Invoice $invoice): int =>
                            $invoice->paidAmount()
                    ),

                'outstanding' =>
                    (int) $invoices->sum(
                        fn (Invoice $invoice): int =>
                            $invoice->outstandingAmount()
                    ),

                'cash_received' =>
                    (int) $payments->sum('amount'),

                'expenses' =>
                    (int) $expenses->sum('amount'),
            ],

            'leases' => $unit->leases
                ->map(fn ($lease): array => [
                    'id' => $lease->id,
                    'tenant_id' => $lease->tenant_id,
                    'tenant' =>
                        $lease->tenant->name
                        ?? $lease->tenant->legal_name,
                    'start_date' =>
                        $lease->start_date->toDateString(),
                    'end_date' =>
                        $lease->end_date?->toDateString(),
                    'status' => $lease->status,
                    'rent_amount' => $lease->rent_amount,
                    'payment_frequency' =>
                        $lease->payment_frequency,
                ])
                ->values()
                ->all(),

            'invoices' => $invoices
                ->map(fn (Invoice $invoice): array => [
                    'id' => $invoice->id,
                    'invoice_number' =>
                        $invoice->invoice_number,
                    'issue_date' =>
                        $invoice->issue_date->toDateString(),
                    'due_date' =>
                        $invoice->due_date->toDateString(),
                    'total_amount' =>
                        $invoice->total_amount,
                    'paid_amount' =>
                        $invoice->paidAmount(),
                    'outstanding_amount' =>
                        $invoice->outstandingAmount(),
                    'status' => $invoice->status,
                ])
                ->values()
                ->all(),
        ];
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
