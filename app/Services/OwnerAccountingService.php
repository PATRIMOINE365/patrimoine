<?php

namespace App\Services;

use App\Models\Building;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Models\Unit;

/**
 * Handles owner-side accounting entries.
 *
 * Patrimoine business rules:
 * - Building expenses are shared according to Building ownership.
 * - All resulting amounts are whole currency units.
 * - The sum of allocated owner shares must exactly equal the source amount.
 * - Any rounding remainder is assigned deterministically so money is
 *   neither lost nor created.
 * - Owner balances may become negative and naturally carry forward.
 */
class OwnerAccountingService
{
    /**
     * Allocate a property expense to Building owners.
     *
     * @return array<int, OwnerTransaction>
     */
    public function allocateExpense(OwnerExpense $expense): array
    {
        return DB::transaction(function () use ($expense): array {
            $expense->refresh();

            $building = Building::query()
                ->with(['ownerships.party'])
                ->lockForUpdate()
                ->findOrFail($expense->building_id);

            $ownerships = $building->ownerships
                ->sortBy('id')
                ->values();

            if ($ownerships->isEmpty()) {
                throw new RuntimeException(
                    __('business.owner.no_ownership')
                );
            }

            $ownershipTotal = (float) $ownerships->sum(
                fn ($ownership) => (float) $ownership->ownership_percentage
            );

            /*
             * Ownership should total exactly 100% before financial
             * allocation is permitted.
             */
            if (abs($ownershipTotal - 100.0) > 0.001) {
                throw new RuntimeException(
                    __('business.owner.ownership_total')
                );
            }

            $remainingAmount = $expense->amount;
            $transactions = [];

            foreach ($ownerships as $index => $ownership) {
                /*
                 * The final owner receives the remaining integer amount.
                 *
                 * This guarantees that allocated shares always sum exactly
                 * to the original expense, even when percentage arithmetic
                 * produces fractional currency values.
                 */
                if ($index === $ownerships->count() - 1) {
                    $ownerShare = $remainingAmount;
                } else {
                    $ownerShare = (int) round(
                        $expense->amount
                        * (float) $ownership->ownership_percentage
                        / 100
                    );

                    $remainingAmount -= $ownerShare;
                }

                $account = OwnerAccount::firstOrCreate([
                    'party_id' => $ownership->party_id,
                ]);

                $transactions[] = OwnerTransaction::create([
                    'owner_account_id' => $account->id,
                    'building_id' => $expense->building_id,
                    'unit_id' => $expense->unit_id,
                    'direction' => 'debit',
                    'category' => 'expense',
                    'amount' => $ownerShare,
                    'transaction_date' => $expense->expense_date,
                    'reference' => $expense->reference,
                    'notes' => sprintf(
                        'Allocated owner share of expense: %s',
                        $expense->description
                    ),
                ]);
            }

            return $transactions;
        });
    }


    /**
     * Post owner rent entitlement when tenant money is actually collected.
     *
     * Patrimoine uses cash-basis owner accounting:
     *
     * - issuing an Invoice does NOT create owner entitlement;
     * - receiving and allocating tenant money DOES create entitlement;
     * - partial tenant payments therefore create partial owner entitlement;
     * - owners are never shown funds that Patrimoine has not actually received.
     *
     * The allocated tenant payment amount is distributed across Building
     * owners according to their ownership percentages.
     *
     * @return array<int, OwnerTransaction>
     */
    public function postCollectedRentEntitlement(
        \App\Models\PaymentAllocation $allocation
    ): array {
        return DB::transaction(function () use ($allocation): array {
            $allocation->refresh();
            /*
            * Payment allocation processing must be idempotent.
            *
            * If entitlement has already been posted for this allocation, return
            * the existing entries rather than crediting owners a second time.
            */
            $existingTransactions = OwnerTransaction::query()
                ->where('payment_allocation_id', $allocation->id)
                ->orderBy('id')
                ->get();

            if ($existingTransactions->isNotEmpty()) {
                return $existingTransactions->all();
            }

            $invoice = $allocation->invoice()
                ->with('lease.unit.building.ownerships')
                ->firstOrFail();

            $lease = $invoice->lease;
            $building = $lease->unit->building;

            $ownerships = $building->ownerships
                ->sortBy('id')
                ->values();

            if ($ownerships->isEmpty()) {
                throw new RuntimeException(
                    __('business.owner.no_ownership')
                );
            }

            $ownershipTotal = (float) $ownerships->sum(
                fn ($ownership) =>
                    (float) $ownership->ownership_percentage
            );

            if (abs($ownershipTotal - 100.0) > 0.001) {
                throw new RuntimeException(
                    __('business.owner.ownership_total')
                );
            }

            /*
            * Only the amount actually received and allocated to the Invoice
            * becomes owner entitlement.
            */
            $collectedAmount = $allocation->amount;

            $remainingAmount = $collectedAmount;
            $transactions = [];

            foreach ($ownerships as $index => $ownership) {
                /*
                * The final owner receives any integer-rounding remainder.
                * This guarantees the total owner entitlement exactly equals
                * the tenant cash allocation.
                */
                if ($index === $ownerships->count() - 1) {
                    $ownerShare = $remainingAmount;
                } else {
                    $ownerShare = (int) round(
                        $collectedAmount
                        * (float) $ownership->ownership_percentage
                        / 100
                    );

                    $remainingAmount -= $ownerShare;
                }

                $account = OwnerAccount::firstOrCreate([
                    'party_id' => $ownership->party_id,
                ]);

                $transactions[] = OwnerTransaction::create([
                    'owner_account_id' => $account->id,
                    'building_id' => $building->id,
                    'unit_id' => $lease->unit_id,
                    'lease_id' => $lease->id,
                    'invoice_id' => $invoice->id,
                    'payment_allocation_id' => $allocation->id,
                    'direction' => 'credit',
                    'category' => 'rent_entitlement',
                    'amount' => $ownerShare,

                    /*
                    * Entitlement date follows the tenant Payment date rather
                    * than the Invoice issue date.
                    */
                    'transaction_date' =>
                        $allocation->payment->payment_date,

                    'reference' =>
                        $allocation->payment->reference
                        ?? $invoice->invoice_number,

                    'notes' =>
                        'Owner share of tenant rent actually collected.',
                ]);
            }

            return $transactions;
        });
    }





    /**
     * Post the management fee applicable to an Invoice.
     *
     * Management fees reduce the amount ultimately payable to the owners.
     *
     * The fee can be:
     * - none;
     * - percentage of Invoice total;
     * - fixed amount.
     *
     * @return array<int, OwnerTransaction>
     */




    /**
 * Post the management fee attributable to collected tenant rent.
 *
 * Patrimoine uses cash-basis owner accounting. Management fees therefore
 * become chargeable only when tenant rent is actually received and allocated
 * to an Invoice.
 *
 * Percentage fees are calculated from the collected allocation.
 *
 * Fixed fees are charged once when the first collection is made against the
 * Invoice.
 *
 * @return array<int, OwnerTransaction>
 */

    public function postManagementFee(
        \App\Models\PaymentAllocation $allocation
    ): array {
        return DB::transaction(
            function () use ($allocation): array {
                $allocation->refresh();

                $invoice = $allocation->invoice()
                    ->with('lease.unit.building.ownerships')
                    ->firstOrFail();

                $lease = $invoice->lease;

                if ($lease->management_fee_type === 'none') {
                    return [];
                }

                /*
                * Percentage fees follow cash actually collected.
                */
                if ($lease->management_fee_type === 'percentage') {
                    $feeAmount = (int) round(
                        $allocation->amount
                        * (float) $lease->management_fee_value
                        / 100
                    );

                    /*
                    * One fee posting per PaymentAllocation protects against
                    * duplicate processing of the same collection.
                    */
                    $alreadyPosted = OwnerTransaction::query()
                        ->where(
                            'payment_allocation_id',
                            $allocation->id
                        )
                        ->where(
                            'category',
                            'management_fee'
                        )
                        ->exists();

                    if ($alreadyPosted) {
                        return [];
                    }
                } elseif ($lease->management_fee_type === 'fixed') {
                    /*
                    * A fixed management fee belongs to the Invoice rather than
                    * each individual Payment. Charge it only once when that
                    * Invoice first receives cash.
                    */
                    $alreadyPosted = OwnerTransaction::query()
                        ->where('invoice_id', $invoice->id)
                        ->where('category', 'management_fee')
                        ->exists();

                    if ($alreadyPosted) {
                        return [];
                    }

                    $feeAmount = (int) round(
                        (float) $lease->management_fee_value
                    );
                } else {
                    throw new RuntimeException(
                        'Unsupported management fee type.'
                    );
                }

                if ($feeAmount <= 0) {
                    return [];
                }

                return $this->allocateOwnerDebit(
                    building: $lease->unit->building,
                    amount: $feeAmount,
                    category: 'management_fee',
                    transactionDate:
                        $allocation->payment->payment_date->toDateString(),
                    leaseId: $lease->id,
                    unitId: $lease->unit_id,
                    invoiceId: $invoice->id,
                    paymentAllocationId: $allocation->id,
                    reference:
                        $allocation->payment->reference
                        ?? $invoice->invoice_number,
                    notes:
                        'Management fee charged against tenant rent actually collected.'
                );
            }
        );
    }








    /**
     * Post the one-time Agent commission configured on a Lease.
     *
     * The commission is shared across Building owners according to ownership
     * percentages and reduces funds due to each owner.
     *
     * This method should normally be called only once when the Lease becomes
     * financially active.
     *
     * @return array<int, OwnerTransaction>
     */
    public function postAgentCommission(\App\Models\Lease $lease): array
    {
        return DB::transaction(function () use ($lease): array {
            $lease->refresh();

            if ($lease->agent_commission_amount <= 0) {
                return [];
            }

            /*
            * Agent commission is a one-time Lease charge.
            */
            $alreadyPosted = OwnerTransaction::query()
                ->where('lease_id', $lease->id)
                ->where('category', 'agent_commission')
                ->exists();

            if ($alreadyPosted) {
                return [];
            }

            $building = $lease->unit()->with('building.ownerships')->firstOrFail()->building;

            return $this->allocateOwnerDebit(
                building: $building,
                amount: $lease->agent_commission_amount,
                category: 'agent_commission',
                transactionDate: $lease->start_date->toDateString(),
                leaseId: $lease->id,
                unitId: $lease->unit_id,
                notes: 'One-time Agent commission charged at Lease commencement.'
            );
        });
    }

    /**
     * Allocate a debit across Building owners according to ownership.
     *
     * This internal helper centralizes percentage validation and integer-safe
     * rounding for management fees, commissions and similar shared charges.
     *
     * @return array<int, OwnerTransaction>
     */
    private function allocateOwnerDebit(
        Building $building,
        int $amount,
        string $category,
        string $transactionDate,
        ?int $leaseId = null,
        ?int $unitId = null,
        ?int $invoiceId = null,
        ?int $paymentAllocationId = null,
        ?string $reference = null,
        ?string $notes = null
    ): array {
        $ownerships = $building->ownerships()
            ->orderBy('id')
            ->get();

        if ($ownerships->isEmpty()) {
            throw new RuntimeException(
                __('business.owner.no_ownership')
            );
        }

        $ownershipTotal = (float) $ownerships->sum(
            fn ($ownership) => (float) $ownership->ownership_percentage
        );

        if (abs($ownershipTotal - 100.0) > 0.001) {
            throw new RuntimeException(
                __('business.owner.ownership_total')
            );
        }

        $remainingAmount = $amount;
        $transactions = [];

        foreach ($ownerships as $index => $ownership) {
            if ($index === $ownerships->count() - 1) {
                $ownerShare = $remainingAmount;
            } else {
                $ownerShare = (int) round(
                    $amount
                    * (float) $ownership->ownership_percentage
                    / 100
                );

                $remainingAmount -= $ownerShare;
            }

            $account = OwnerAccount::firstOrCreate([
                'party_id' => $ownership->party_id,
            ]);

            $transactions[] = OwnerTransaction::create([
                'owner_account_id' => $account->id,
                'building_id' => $building->id,
                'unit_id' => $unitId,
                'lease_id' => $leaseId,
                'invoice_id' => $invoiceId,
                'payment_allocation_id' => $paymentAllocationId,
                'direction' => 'debit',
                'category' => $category,
                'amount' => $ownerShare,
                'transaction_date' => $transactionDate,
                'reference' => $reference,
                'notes' => $notes,
            ]);
        }

        return $transactions;
    }
}
