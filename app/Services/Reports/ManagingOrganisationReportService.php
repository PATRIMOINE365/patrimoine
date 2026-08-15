<?php

namespace App\Services\Reports;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\Unit;
use Carbon\Carbon;
use RuntimeException;

/**
 * Portfolio-wide Patrimoine management report.
 *
 * This is the overall operational and financial view used by the
 * organisation managing the properties.
 */
class ManagingOrganisationReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(
        ?string $from = null,
        ?string $to = null
    ): array {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $invoiceQuery = Invoice::query()
            ->where('status', '!=', 'cancelled');

        $this->applyDateRange(
            $invoiceQuery,
            'issue_date',
            $fromDate,
            $toDate
        );

        $invoices = $invoiceQuery->get();

        $paymentQuery = Payment::query();

        $this->applyDateRange(
            $paymentQuery,
            'payment_date',
            $fromDate,
            $toDate
        );

        $payments = $paymentQuery->get();

        $expenseQuery = OwnerExpense::query();

        $this->applyDateRange(
            $expenseQuery,
            'expense_date',
            $fromDate,
            $toDate
        );

        $expenses = $expenseQuery->get();

        $ownerTransactionQuery =
            OwnerTransaction::query();

        $this->applyDateRange(
            $ownerTransactionQuery,
            'transaction_date',
            $fromDate,
            $toDate
        );

        $ownerTransactions =
            $ownerTransactionQuery->get();

        $fundAccounts =
            TenantFundAccount::query()->get();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'portfolio' => [
                'buildings' => Building::count(),
                'units' => Unit::count(),
                'owner_accounts' =>
                    OwnerAccount::count(),
            ],
'billing' => [
    /*
     * Preserve the existing generic totals as the complete receivable
     * position for backward compatibility.
     */
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

    /*
     * V1.0.1 explicitly separates contractual rent from Security Deposit
     * close-out debt.
     */
    'rent_invoiced' =>
        (int) $invoices
            ->where('type', 'rent')
            ->sum('total_amount'),

    'security_deposit_debt_invoiced' =>
        (int) $invoices
            ->where('type', 'security_deposit_debt')
            ->sum('total_amount'),

    'rent_outstanding' =>
        (int) $invoices
            ->where('type', 'rent')
            ->sum(
                fn (Invoice $invoice): int =>
                    $invoice->outstandingAmount()
            ),

    'security_deposit_debt_outstanding' =>
        (int) $invoices
            ->where('type', 'security_deposit_debt')
            ->sum(
                fn (Invoice $invoice): int =>
                    $invoice->outstandingAmount()
            ),

    'total_outstanding' =>
        (int) $invoices->sum(
            fn (Invoice $invoice): int =>
                $invoice->outstandingAmount()
        ),

    'cash_received' =>
        (int) $payments->sum('amount'),
],

            'owner_accounting' => [
                'rent_entitlement' =>
                    $this->ledgerCategory(
                        $ownerTransactions,
                        'rent_entitlement',
                        'credit'
                    ),

                'management_fees' =>
                    $this->ledgerCategory(
                        $ownerTransactions,
                        'management_fee',
                        'debit'
                    ),

                'agent_commissions' =>
                    $this->ledgerCategory(
                        $ownerTransactions,
                        'agent_commission',
                        'debit'
                    ),

                'owner_expenses' =>
                    (int) $expenses->sum('amount'),

                'owner_payouts' =>
                    $this->ledgerCategory(
                        $ownerTransactions,
                        'payout',
                        'debit'
                    ),

                'owner_funds_held' =>
                    (int) OwnerAccount::query()
                        ->get()
                        ->sum(
                            fn (OwnerAccount $account): int =>
                                max(0, $account->balance())
                        ),
            ],

            'tenant_funds' => [
                'rent_reserve' =>
                    $this->fundBalance(
                        $fundAccounts,
                        'rent_reserve'
                    ),

                'consumable_advance' =>
                    $this->fundBalance(
                        $fundAccounts,
                        'consumable_advance'
                    ),

                'security_deposit' =>
                    $this->fundBalance(
                        $fundAccounts,
                        'security_deposit'
                    ),
            ],
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

    private function fundBalance(
        $accounts,
        string $type
    ): int {
        return (int) $accounts
            ->where('type', $type)
            ->sum(
                fn (TenantFundAccount $account): int =>
                    $account->balance()
            );
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
