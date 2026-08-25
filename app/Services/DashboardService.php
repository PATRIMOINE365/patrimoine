<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\RentIncrement;
use App\Models\TenantFundAccount;
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
     * Return the primary operational and financial dashboard metrics.
     *
     * @param  Carbon|null  $asOfDate
     *                                 Date against which due/overdue calculations are evaluated.
     *                                 Defaults to the current application date.
     * @return array<string, int>
     */
    public function metrics(?Carbon $asOfDate = null): array
    {
        $asOfDate ??= now();

        $monthStart = $asOfDate->copy()->startOfMonth();
        $monthEnd = $asOfDate->copy()->endOfMonth();

        return [
            'total_buildings' => Building::query()->count(),
            'total_units' => Unit::query()->count(),

            'occupied_units' => $this->occupiedUnitCount($asOfDate),
            'vacant_units' => $this->vacantUnitCount($asOfDate),

            'rent_due' => $this->rentDueAmount($asOfDate),
            'rent_overdue' => $this->rentOverdueAmount($asOfDate),

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
            'vacant_commercial_units' => $this->vacantUnitCount(
                $asOfDate,
                commercial: true
            ),

            'vacant_residential_units' => $this->vacantUnitCount(
                $asOfDate,
                commercial: false
            ),

            'tenant_funds_held' => $this->tenantFundsHeld(),

            'leases_expiring_90_days' => $this->expiringLeases(
                $asOfDate,
                90
            )->count(),

            'increments_upcoming_60_days' => $this->upcomingIncrements(
                $asOfDate,
                60
            )->count(),
        ];
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
        return Invoice::query()
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '<=', $asOfDate->toDateString())
            ->get()
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
        return Invoice::query()
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate('due_date', '<', $asOfDate->toDateString())
            ->get()
            ->sum(
                fn (Invoice $invoice): int => $invoice->outstandingAmount()
            );
    }

    /**
     * Return tenant money actually received during a date range.
     *
     * Patrimoine uses cash-basis owner accounting, and this dashboard
     * collection metric similarly represents actual Payments rather than
     * invoices issued.
     */
    public function rentCollectedBetween(
        Carbon $from,
        Carbon $to
    ): int {
        return (int) Payment::query()
            ->whereBetween('payment_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->sum('amount');
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

    public function upcomingInvoices(
        Carbon $asOfDate,
        int $days = 7
    ) {
        $endDate = $asOfDate->copy()->addDays($days);

        return Invoice::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
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
