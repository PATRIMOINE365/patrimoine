<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: records a tenant expense as an unpaid EXP- Invoice.
 *
 * Expenses no longer settle themselves. Recording one creates a
 * receivable Invoice with itemized lines; the money leaves a tenant
 * fund account only when the user explicitly pays that Invoice through
 * the Pay flow, and that payment can later be cancelled.
 */
class TenantExpenseInvoiceService
{
    public function __construct(
        private readonly ExpenseInvoiceNumberService $numbers
    ) {
    }

    /**
     * Record a validated batch of expense lines as one expense Invoice.
     *
     * @param  array<int, array{description: mixed, amount: mixed}>  $lines
     *
     * @throws RuntimeException When the batch violates billing rules.
     */
    public function record(
        Lease $lease,
        array $lines,
        string $transactionDate,
        ?string $reference = null
    ): Invoice {
        return DB::transaction(function () use (
            $lease,
            $lines,
            $transactionDate,
            $reference
        ): Invoice {
            $lease = Lease::query()
                ->lockForUpdate()
                ->findOrFail($lease->id);

            /*
             * Draft Leases hold no tenant relationship yet and must not
             * accumulate receivables.
             */
            if ($lease->status === 'draft') {
                throw new RuntimeException(
                    __('business.tenant.expense_draft_lease')
                );
            }

            $lines = array_values($lines);

            if ($lines === []) {
                throw new RuntimeException(
                    __('business.tenant.expense_lines_required')
                );
            }

            $total = 0;

            foreach ($lines as $line) {
                $amount = (int) ($line['amount'] ?? 0);

                if ($amount <= 0) {
                    throw new RuntimeException(
                        __('business.tenant.expense_positive')
                    );
                }

                if (trim((string) ($line['description'] ?? '')) === '') {
                    throw new RuntimeException(
                        __('business.tenant.expense_description_required')
                    );
                }

                $total += $amount;
            }

            $invoiceNumber = $this->numbers->next();

            /*
             * Expense invoices carry no VAT and no billing period; the
             * expense date stands in for both period bounds so every
             * date-driven listing keeps working.
             */
            $invoice = Invoice::create([
                'lease_id' => $lease->id,
                'invoice_number' => $invoiceNumber,
                'type' => 'expense',
                'period_start' => $transactionDate,
                'period_end' => $transactionDate,
                'issue_date' => $transactionDate,
                'due_date' => $transactionDate,
                'status' => 'issued',
                'total_amount' => $total,
                'vat_rate' => 0,
                'net_amount' => $total,
                'vat_amount' => 0,
                'notes' => $reference !== null
                    && trim($reference) !== ''
                        ? trim($reference)
                        : null,
            ]);

            foreach ($lines as $line) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => trim((string) $line['description']),
                    'amount' => (int) $line['amount'],
                ]);
            }

            return $invoice
                ->refresh()
                ->load('lines');
        });
    }
}
