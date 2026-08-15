<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\Carbon;

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
     * @param Carbon|null $asOfDate
     *     Date against which due/overdue calculations are evaluated.
     *     Defaults to the current application date.
     *
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
        ];
    }

    /**
     * Return the number of Units considered occupied as of a given date.
     *
     * A Unit is occupied when it has an active or notice-stage Lease whose
     * contractual dates include the requested date.
     *
     * Draft and terminated Leases never create occupancy.
     */
    public function occupiedUnitCount(Carbon $asOfDate): int
    {
        return Unit::query()
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
    public function vacantUnitCount(Carbon $asOfDate): int
    {
        return Unit::query()->count()
            - $this->occupiedUnitCount($asOfDate);
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
            fn (Invoice $invoice): int =>
                $invoice->outstandingAmount()
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
            fn (Invoice $invoice): int =>
                $invoice->outstandingAmount()
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
                fn (OwnerAccount $account): int =>
                    max(0, $account->balance())
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Invoice>
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
            ->get();
    }

    /**
     * Return unpaid invoices becoming due within the requested number of
     * days after the supplied as-of date.
     *
     * This powers the dashboard's "upcoming due tenants" view.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Invoice>
     */
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
            ->get();
    }
}
