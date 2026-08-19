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
 * Handles controlled consumption of a Lease's Rent Reserve.
 *
 * Patrimoine business rules:
 *
 * - Only a rent_reserve account may be consumed.
 * - The fund account must be active.
 * - The Lease must be in termination notice.
 * - A termination_notice_date must exist.
 * - Reserve consumption cannot exceed the account balance.
 * - Reserve consumption cannot exceed the Invoice outstanding amount.
 * - Consumption reduces the Invoice's outstanding balance.
 * - Consumed rent becomes owner entitlement because Patrimoine is now
 *   releasing previously-held tenant money as earned rent.
 * - Every operation occurs inside one database transaction.
 */
class RentReserveService
{
    /**
     * Consume Rent Reserve to settle all or part of an Invoice.
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
             * Lock both financial records so concurrent operations cannot
             * consume the same reserve or Invoice balance twice.
             */
            $account = TenantFundAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($account->type !== 'rent_reserve') {
                throw new RuntimeException(
                    __('business.rent_reserve.wrong_account_type')
                );
            }

            if ($account->status !== 'active') {
                throw new RuntimeException(
                    __('business.rent_reserve.account_closed')
                );
            }

            $lease = $account->lease;

            /*
             * Reserve funds remain protected until formal termination
             * notice has been recorded.
             */
            if (
                $lease->status !== 'notice'
                || $lease->termination_notice_date === null
            ) {
                throw new RuntimeException(
                    __('business.rent_reserve.before_notice')
                );
            }
            if ($invoice->lease_id !== $lease->id) {
                throw new RuntimeException(
                    __('business.rent_reserve.wrong_invoice_lease')
                );
            }

            /*
             * Rent Reserve exists specifically to cover contractual rent during the
             * termination-notice period.
             *
             * Non-rent receivables, such as Security Deposit close-out debt, must not
             * consume Rent Reserve or enter the owner rent-entitlement pipeline.
             */
            if (! $invoice->isRentInvoice()) {
                throw new RuntimeException(
                    __('business.rent_reserve.rent_only')
                );
            }

            if ($amount <= 0) {
                throw new RuntimeException(
                    __('business.rent_reserve.amount_positive')
                );
            }

            if ($amount > $account->balance()) {
                throw new RuntimeException(
                    __('business.rent_reserve.insufficient_balance')
                );
            }

            if ($amount > $invoice->outstandingAmount()) {
                throw new RuntimeException(
                    __('business.rent_reserve.exceeds_invoice')
                );
            }

            /*
             * Record the actual movement out of protected Rent Reserve.
             *
             * Invoice::paidAmount() includes these rent_consumption
             * transactions, so this immediately reduces the outstanding
             * tenant obligation.
             */
            $transaction = TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'invoice_id' => $invoice->id,
                'direction' => 'debit',
                'category' => 'rent_consumption',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'notes' => 'Rent Reserve consumed against tenant rent Invoice.',
            ]);

            /*
             * Once reserve money is consumed as rent, it becomes owner
             * entitlement just like ordinary rent cash collected.
             */
            $this->postOwnerEntitlement(
                invoice: $invoice,
                amount: $amount,
                transactionDate: $transactionDate
            );

            /*
             * Keep the Invoice lifecycle synchronized with its newly
             * calculated outstanding balance.
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
     * Post owner entitlement for Rent Reserve consumed as rent.
     *
     * Ownership is applied at Building level. Integer-safe rounding
     * guarantees that the total owner credits exactly equal the amount
     * consumed from Rent Reserve.
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
                __('business.rent_reserve.no_ownership')
            );
        }

        $ownershipTotal = (float) $ownerships->sum(
            fn ($ownership) => (float) $ownership->ownership_percentage
        );

        if (abs($ownershipTotal - 100.0) > 0.001) {
            throw new RuntimeException(
                __('business.rent_reserve.ownership_total')
            );
        }

        $remainingAmount = $amount;

        foreach ($ownerships as $index => $ownership) {
            /*
             * Assign rounding remainder to the final owner so no money
             * disappears or is created.
             */
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
                'notes' => 'Owner rent entitlement released from tenant Rent Reserve.',
            ]);
        }
    }
}
