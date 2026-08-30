<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\RentIncrement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Provides the read-side calculations used by the Patrimoine dashboard.
 *
 * The dashboard deliberately derives its values from transactional data
 * instead of storing duplicate summary fields.
 *
 * This keeps dashboard figures aligned with the accounting ledger and
 * avoids situations where summary counters drift away from source data.
 */
class DashboardService
{
    /**
     * Relations consulted by Invoice::outstandingAmount().
     *
     * Eager-loading them lets settlement sums run against the loaded
     * collections instead of issuing per-invoice queries (N+1).
     *
     * @var list<string>
     */
    private const INVOICE_SETTLEMENT_RELATIONS = [
        'paymentAllocations',
        'tenantFundTransactions',
        'securityDepositApplications',
    ];

    /**
     * Return the primary operational and financial dashboard metrics.
     *
     * @param  Carbon|null  $asOfDate
     *                                 Date against which due/overdue calculations are evaluated.
     *                                 Defaults to the current application date.
     * @param  int|null  $expiringLeaseCount
     *                                        Pre-computed count of leases expiring within 90 days.
     *                                        Pass it when the caller already fetched the list so the
     *                                        query does not run a second time.
     * @param  int|null  $upcomingIncrementCount
     *                                            Pre-computed count of increments effective within 60
     *                                            days, for the same single-execution reason.
     * @return array<string, int>
     */
    public function metrics(
        ?Carbon $asOfDate = null,
        ?int $expiringLeaseCount = null,
        ?int $upcomingIncrementCount = null
    ): array {
        $asOfDate ??= now();

        $monthStart = $asOfDate->copy()->startOfMonth();
        $monthEnd = $asOfDate->copy()->endOfMonth();

        /*
         * V1.0.9: occupancy is derived from four counts computed once.
         *
         * is_commercial is a non-nullable boolean, so the residential
         * split is exactly the remainder of the commercial split.
         */
        $totalUnits = Unit::query()->count();
        $occupiedUnits = $this->occupiedUnitCount($asOfDate);

        $commercialUnits = Unit::query()
            ->where('is_commercial', true)
            ->count();

        $occupiedCommercialUnits = $this->occupiedUnitCount(
            $asOfDate,
            commercial: true
        );

        $vacantUnits = $totalUnits - $occupiedUnits;
        $vacantCommercialUnits = $commercialUnits - $occupiedCommercialUnits;
        $vacantResidentialUnits = $vacantUnits - $vacantCommercialUnits;

        /*
         * V1.0.9: due and overdue share one invoice materialization —
         * every overdue invoice is by definition also due.
         */
        $dueInvoices = $this->openRentInvoicesDueBy($asOfDate);

        $rentDue = (int) $dueInvoices->sum(
            fn (Invoice $invoice): int => $invoice->outstandingAmount()
        );

        $rentOverdue = (int) $dueInvoices
            ->filter(
                fn (Invoice $invoice): bool => $invoice->due_date->toDateString()
                    < $asOfDate->toDateString()
            )
            ->sum(
                fn (Invoice $invoice): int => $invoice->outstandingAmount()
            );

        return [
            'total_buildings' => Building::query()->count(),
            'total_units' => $totalUnits,

            /*
             * V1.0.29: an organisation with no lease at all has not
             * started yet, and the dashboard offers it the guided wizard
             * rather than an empty page.
             */
            'total_leases' => Lease::query()->count(),

            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,

            'rent_due' => $rentDue,
            'rent_overdue' => $rentOverdue,

            'rent_collected_this_month' => $this->rentCollectedBetween(
                $monthStart,
                $monthEnd
            ),

            'owner_funds_held' => $this->ownerFundsHeld(),

            'management_fees_this_month' => $this->managementFeesBetween(
                $monthStart,
                $monthEnd
            ),

            /*
             * V1.0.7 portfolio-health additions.
             */
            'vacant_commercial_units' => $vacantCommercialUnits,

            'vacant_residential_units' => $vacantResidentialUnits,

            'tenant_funds_held' => $this->tenantFundsHeld(),

            'leases_expiring_90_days' => $expiringLeaseCount
                ?? $this->expiringLeases($asOfDate, 90)->count(),

            'increments_upcoming_60_days' => $upcomingIncrementCount
                ?? $this->upcomingIncrements($asOfDate, 60)->count(),
        ];
    }

    /**
     * Fetch open rent invoices due on or before the as-of date, with the
     * settlement relations preloaded, in one materialization.
     *
     * @return Collection<int, Invoice>
     */
    private function openRentInvoicesDueBy(Carbon $asOfDate)
    {
        return Invoice::query()
            ->with(self::INVOICE_SETTLEMENT_RELATIONS)
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '<=', $asOfDate->toDateString())
            ->get();
    }

    /**
     * V1.0.7: occupancy rate in whole percent (0 when no units exist).
     */
    public function occupancyRate(Carbon $asOfDate): int
    {
        $total = Unit::query()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(
            $this->occupiedUnitCount($asOfDate) * 100 / $total
        );
    }

    /**
     * V1.0.7: tenant money currently held across all active fund accounts
     * (rent reserves, consumable advances and security deposits). Only
     * positive balances count, mirroring ownerFundsHeld().
     */
    public function tenantFundsHeld(): int
    {
        return TenantFundAccount::query()
            ->where('status', 'active')
            ->with('transactions')
            ->get()
            ->sum(
                fn (TenantFundAccount $account): int => max(
                    0,
                    $account->creditedAmount() - $account->debitedAmount()
                )
            );
    }

    /**
     * V1.0.7: rent actually collected per month for the trailing N months
     * (oldest first) — the dashboard's collections trend.
     *
     * @return array<int, array{month: string, amount: int}>
     */
    public function collectionsTrend(
        Carbon $asOfDate,
        int $months = 6
    ): array {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = $asOfDate->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $trend[] = [
                'month' => $start->format('Y-m'),
                'amount' => $this->rentCollectedBetween($start, $end),
            ];
        }

        return $trend;
    }

    /**
     * V1.0.7: current leases whose contractual end date falls within the
     * requested window — the expiry-management list.
     *
     * @return Collection<int, Lease>
     */
    public function expiringLeases(
        Carbon $asOfDate,
        int $days = 90
    ) {
        return Lease::query()
            ->with([
                'tenant',
                'unit.building',
            ])
            ->whereIn('status', ['active', 'notice'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $asOfDate->toDateString())
            ->whereDate(
                'end_date',
                '<=',
                $asOfDate->copy()->addDays($days)->toDateString()
            )
            ->orderBy('end_date')
            ->get();
    }

    /**
     * V1.0.7: scheduled rent increments becoming effective within the
     * requested window.
     *
     * @return Collection<int, RentIncrement>
     */
    public function upcomingIncrements(
        Carbon $asOfDate,
        int $days = 60
    ) {
        return RentIncrement::query()
            ->with('lease.tenant')
            ->where('status', 'scheduled')
            ->whereDate('effective_date', '>=', $asOfDate->toDateString())
            ->whereDate(
                'effective_date',
                '<=',
                $asOfDate->copy()->addDays($days)->toDateString()
            )
            ->orderBy('effective_date')
            ->get();
    }

    /**
     * Return the number of Units considered occupied as of a given date.
     *
     * A Unit is occupied when it has an active or notice-stage Lease whose
     * contractual dates include the requested date.
     *
     * Draft and terminated Leases never create occupancy.
     */
    public function occupiedUnitCount(
        Carbon $asOfDate,
        ?bool $commercial = null
    ): int {
        return Unit::query()
            ->when(
                $commercial !== null,
                fn ($query) => $query->where('is_commercial', $commercial)
            )
            ->whereHas('leases', function ($query) use ($asOfDate) {
                $query
                    ->whereIn('status', ['active', 'notice'])
                    ->whereDate('start_date', '<=', $asOfDate->toDateString())
                    ->where(function ($query) use ($asOfDate) {
                        $query
                            ->whereNull('end_date')
                            ->orWhereDate(
                                'end_date',
                                '>=',
                                $asOfDate->toDateString()
                            );
                    });
            })
            ->count();
    }

    /**
     * Return the number of Units considered vacant as of a given date.
     *
     * Occupancy is derived from Lease records rather than stored directly
     * on the Unit, so vacancy is simply total Units minus occupied Units.
     */
    public function vacantUnitCount(
        Carbon $asOfDate,
        ?bool $commercial = null
    ): int {
        $total = Unit::query()
            ->when(
                $commercial !== null,
                fn ($query) => $query->where('is_commercial', $commercial)
            )
            ->count();

        return $total
            - $this->occupiedUnitCount($asOfDate, $commercial);
    }

    /**
     * Return the total amount currently due on issued or partially settled
     * invoices whose due date has arrived.
     *
     * Only the outstanding portion of each Invoice is counted.
     */
    public function rentDueAmount(Carbon $asOfDate): int
    {
        return (int) $this->openRentInvoicesDueBy($asOfDate)
            ->sum(
                fn (Invoice $invoice): int => $invoice->outstandingAmount()
            );
    }

    /**
     * Return the amount that is overdue as of the requested date.
     *
     * An Invoice becomes overdue only after its due date has passed.
     * Invoices due exactly on the as-of date belong to rent_due but are
     * not yet included in rent_overdue.
     */
    public function rentOverdueAmount(Carbon $asOfDate): int
    {
        return (int) Invoice::query()
            ->with(self::INVOICE_SETTLEMENT_RELATIONS)
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '<', $asOfDate->toDateString())
            ->get()
            ->sum(
                fn (Invoice $invoice): int => $invoice->outstandingAmount()
            );
    }

    /**
     * Return rent money actually received during a date range.
     *
     * Patrimoine uses cash-basis owner accounting, and this dashboard
     * collection metric similarly represents actual Payments rather than
     * invoices issued.
     *
     * V1.0.9: only Payment money allocated to rent Invoices counts.
     * Since V1.0.8 the payments table also carries expense-invoice
     * settlements and fund top-ups, which are not rent collections and
     * must not inflate this metric or the collections trend.
     */
    public function rentCollectedBetween(
        Carbon $from,
        Carbon $to
    ): int {
        return (int) PaymentAllocation::query()
            ->join(
                'payments',
                'payments.id',
                '=',
                'payment_allocations.payment_id'
            )
            ->join(
                'invoices',
                'invoices.id',
                '=',
                'payment_allocations.invoice_id'
            )
            ->where('invoices.type', 'rent')
            ->whereBetween('payments.payment_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->sum('payment_allocations.amount');
    }

    /**
     * Return positive owner balances currently held by Patrimoine.
     *
     * Negative owner balances are obligations owed by the owner and must
     * not reduce funds genuinely being held for unrelated owners.
     *
     * Example:
     *
     * Owner A balance:  10,000
     * Owner B balance:  -3,000
     *
     * Owner Funds Held = 10,000, not 7,000.
     */
    public function ownerFundsHeld(): int
    {
        return OwnerAccount::query()
            ->get()
            ->sum(
                fn (OwnerAccount $account): int => max(0, $account->balance())
            );
    }

    /**
     * Return management fees recognized during a date range.
     *
     * Management fees are represented as debit OwnerTransactions.
     */
    public function managementFeesBetween(
        Carbon $from,
        Carbon $to
    ): int {
        return (int) OwnerTransaction::query()
            ->where('direction', 'debit')
            ->where('category', 'management_fee')
            ->whereBetween('transaction_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->sum('amount');
    }

    /**
     * Return overdue tenant obligations as of a given date.
     *
     * The result is intended for dashboard tables and later API responses.
     *
     * @return Collection<int, Invoice>
     */
    public function overdueInvoices(Carbon $asOfDate)
    {
        return Invoice::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
                ...self::INVOICE_SETTLEMENT_RELATIONS,
            ])
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '<', $asOfDate->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            /*
             * V1.0.7: an invoice fully covered through fund consumption can
             * retain a non-paid status while owing nothing. Zero-outstanding
             * rows must not appear as overdue (or inflate the notification
             * count) — the tenant owes nothing on them.
             */
            ->filter(
                fn (Invoice $invoice): bool => $invoice->outstandingAmount() > 0
            )
            ->values();
    }

    /**
     * Return unpaid invoices becoming due within the requested number of
     * days after the supplied as-of date.
     *
     * This powers the dashboard's "upcoming due tenants" view.
     *
     * @return Collection<int, Invoice>
     */
    /**
     * V1.0.8: unpaid tenant expense Invoices.
     *
     * Expense invoices fall due on their expense date, so any open
     * balance is immediately actionable through the Pay flow.
     */
    public function unpaidExpenseInvoices()
    {
        return Invoice::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
                ...self::INVOICE_SETTLEMENT_RELATIONS,
            ])
            ->where('type', 'expense')
            ->whereIn('status', ['issued', 'partial'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->filter(
                fn (Invoice $invoice): bool => $invoice->outstandingAmount() > 0
            )
            ->values();
    }

    /**
     * V1.0.8: owner expense bills that still carry an unpaid balance.
     */
    public function unpaidOwnerExpenseBills()
    {
        return OwnerExpenseBill::query()
            ->with('ownerAccount.party')
            ->orderBy('bill_date')
            ->orderBy('id')
            ->get()
            ->filter(
                fn (OwnerExpenseBill $bill): bool => $bill->outstandingAmount() > 0
            )
            ->values();
    }

    /**
     * V1.0.35: money received that has not been filed anywhere yet.
     *
     * A payment settles invoices oldest first and stops when they run
     * out. Whatever is left over is not lost — it waits to be classified
     * into a tenant fund through the Pay flow — but until somebody does
     * that, it sits on the payment and appears on no balance, in no fund
     * and nowhere in the ledger.
     *
     * Nothing used to chase it, so it could sit for ever: the tenant is
     * ahead and does not look it, and the next statement asks them again
     * for money they have already handed over. Surfacing it is the whole
     * fix — the money was always retrievable, it was just invisible.
     *
     * @return Collection<int, Payment>
     */
    public function unclassifiedPayments()
    {
        return Payment::query()
            ->with('lease.tenant', 'lease.unit.building')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->filter(
                fn (Payment $payment): bool => $this->remainingUnclassified($payment) > 0
            )
            ->values();
    }

    /**
     * What is left of a payment once its invoices and its fund
     * classifications are accounted for.
     *
     * The same arithmetic PaymentController reports as
     * `remaining_unclassified_amount`, so the bell and the payment screen
     * can never disagree about how much is still waiting.
     */
    public function remainingUnclassified(
        Payment $payment
    ): int {
        $classified = (int) TenantFundTransaction::query()
            ->where('payment_id', $payment->id)
            ->where('direction', 'credit')
            ->whereIn('category', [
                'reserve_funding',
                'advance_funding',
                'deposit_funding',
            ])
            ->sum('amount');

        return max(
            0,
            $payment->unallocatedAmount() - $classified
        );
    }

    public function upcomingInvoices(
        Carbon $asOfDate,
        int $days = 7
    ) {
        $endDate = $asOfDate->copy()->addDays($days);

        return Invoice::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
                ...self::INVOICE_SETTLEMENT_RELATIONS,
            ])
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '>=', $asOfDate->toDateString())
            ->whereDate('due_date', '<=', $endDate->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            // V1.0.7: see overdueInvoices — zero-outstanding rows excluded.
            ->filter(
                fn (Invoice $invoice): bool => $invoice->outstandingAmount() > 0
            )
            ->values();
    }
}
