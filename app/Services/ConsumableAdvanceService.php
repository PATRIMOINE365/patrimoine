<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles consumption of a Lease's Consumable Advance.
 *
 * Patrimoine business rules:
 *
 * - Only a consumable_advance account may be consumed.
 * - The fund account must be active.
 * - Consumable Advance may be used during the normal Lease lifecycle.
 * - It may settle only Invoices belonging to the same Lease.
 * - Consumption cannot exceed the account balance.
 * - Consumption cannot exceed the Invoice outstanding amount.
 * - Consumed advance reduces the Invoice's outstanding balance.
 * - Consumed rent becomes owner entitlement because previously-held
 *   tenant money is now recognized as earned rent.
 * - Every operation occurs inside one database transaction.
 */
class ConsumableAdvanceService
{
    /**
     * Consume tenant advance money to settle all or part of an Invoice.
     */
    public function consume(
        TenantFundAccount $account,
        Invoice $invoice,
        int $amount,
        string $transactionDate
    ): TenantFundTransaction {
        return DB::transaction(function () use (
            $account,
            $invoice,
            $amount,
            $transactionDate
        ): TenantFundTransaction {
            /*
             * Lock both records to prevent concurrent requests from
             * consuming the same available balance or Invoice amount twice.
             */
            $account = TenantFundAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($account->type !== 'consumable_advance') {
                throw new RuntimeException(
                    'Only a Consumable Advance account can be consumed by this service.'
                );
            }

            if ($account->status !== 'active') {
                throw new RuntimeException(
                    'Consumable Advance account is closed.'
                );
            }

            $lease = $account->lease;

            /*
             * Draft Leases should never consume tenant money.
             *
             * Active, notice and terminated Leases may still legitimately
             * use available advance funds to settle outstanding rent.
             */
            if ($lease->status === 'draft') {
                throw new RuntimeException(
                    'Consumable Advance cannot be used for a draft Lease.'
                );
            }


if ($invoice->lease_id !== $lease->id) {
    throw new RuntimeException(
        'The Invoice does not belong to the Consumable Advance Lease.'
    );
}

/*
 * Consumable Advance may be applied to contractual rent only.
 *
 * Other tenant receivables remain collectible through the ordinary
 * Payment allocation workflow and must never create owner rent entitlement.
 */
if (! $invoice->isRentInvoice()) {
    throw new RuntimeException(
        'Consumable Advance can only settle rent invoices.'
    );
}

if ($amount <= 0) {
    throw new RuntimeException(
        'Consumable Advance amount must be greater than zero.'
    );
}



            if ($amount > $account->balance()) {
                throw new RuntimeException(
                    'Consumable Advance balance is insufficient.'
                );
            }

            if ($amount > $invoice->outstandingAmount()) {
                throw new RuntimeException(
                    'Consumable Advance exceeds the Invoice outstanding amount.'
                );
            }

            /*
             * Record the movement from tenant-held advance into rent.
             *
             * Invoice::paidAmount() must include this category so the
             * Invoice balance is reduced immediately.
             */
            $transaction = TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'invoice_id' => $invoice->id,
                'direction' => 'debit',
                'category' => 'advance_consumption',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'notes' => 'Consumable Advance applied against tenant rent Invoice.',
            ]);

            /*
             * Once Advance is consumed as rent, it becomes owner
             * entitlement exactly like cash collected from the tenant.
             */
            $this->postOwnerEntitlement(
                invoice: $invoice,
                amount: $amount,
                transactionDate: $transactionDate
            );

            /*
             * Keep Invoice lifecycle synchronized with its derived balance.
             */
            $invoice->refresh();

            $invoice->update([
                'status' => $invoice->outstandingAmount() === 0
                    ? 'paid'
                    : 'partial',
            ]);

            return $transaction->refresh();
        });
    }

    /**
     * Create owner rent entitlement for consumed advance.
     *
     * Building ownership percentages determine each owner's share.
     * The final owner receives any integer rounding remainder.
     */
    private function postOwnerEntitlement(
        Invoice $invoice,
        int $amount,
        string $transactionDate
    ): void {
        $invoice->loadMissing(
            'lease.unit.building.ownerships'
        );

        $lease = $invoice->lease;
        $building = $lease->unit->building;

        $ownerships = $building->ownerships
            ->sortBy('id')
            ->values();

        if ($ownerships->isEmpty()) {
            throw new RuntimeException(
                'Building has no ownership allocations.'
            );
        }

        $ownershipTotal = (float) $ownerships->sum(
            fn ($ownership) =>
                (float) $ownership->ownership_percentage
        );

        if (abs($ownershipTotal - 100.0) > 0.001) {
            throw new RuntimeException(
                'Building ownership percentages must total 100%.'
            );
        }

        $remainingAmount = $amount;

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

            $ownerAccount = OwnerAccount::firstOrCreate([
                'party_id' => $ownership->party_id,
            ]);

            OwnerTransaction::create([
                'owner_account_id' => $ownerAccount->id,
                'building_id' => $building->id,
                'unit_id' => $lease->unit_id,
                'lease_id' => $lease->id,
                'invoice_id' => $invoice->id,
                'direction' => 'credit',
                'category' => 'rent_entitlement',
                'amount' => $ownerShare,
                'transaction_date' => $transactionDate,
                'reference' => $invoice->invoice_number,
                'notes' => 'Owner rent entitlement released from tenant Consumable Advance.',
            ]);
        }
    }
}
