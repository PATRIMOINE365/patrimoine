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
                    __('business.consumable_advance.wrong_account_type')
                );
            }

            if ($account->status !== 'active') {
                throw new RuntimeException(
                    __('business.consumable_advance.account_closed')
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
                    __('business.consumable_advance.draft_lease')
                );
            }

            if ($invoice->lease_id !== $lease->id) {
                throw new RuntimeException(
                    __('business.consumable_advance.wrong_invoice_lease')
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
                    __('business.consumable_advance.rent_only')
                );
            }

            if ($amount <= 0) {
                throw new RuntimeException(
                    __('business.consumable_advance.amount_positive')
                );
            }

            if ($amount > $account->balance()) {
                throw new RuntimeException(
                    __('business.consumable_advance.insufficient_balance')
                );
            }

            if ($amount > $invoice->outstandingAmount()) {
                throw new RuntimeException(
                    __('business.consumable_advance.exceeds_invoice')
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
             * V1.0.5 Phase 3 Step 4:
             *
             * The persisted tenant-fund debit and its Financial Journal
             * posting share the same business transaction. Before the
             * controlled accounting cutover this call is a no-op.
             */
            app(
                \App\Services\Accounting\TenantFundConsumptionJournalService::class
            )->post(
                $transaction
            );


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
                __('business.consumable_advance.no_ownership')
            );
        }

        $ownershipTotal = (float) $ownerships->sum(
            fn ($ownership) => (float) $ownership->ownership_percentage
        );

        if (abs($ownershipTotal - 100.0) > 0.001) {
            throw new RuntimeException(
                __('business.consumable_advance.ownership_total')
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
