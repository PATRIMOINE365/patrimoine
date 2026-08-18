<?php

namespace App\Services\Documents;

use App\Models\OwnerTransaction;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * Generates PDF receipts for money received directly from a Property Owner.
 *
 * Owner deposits are recorded in the owner ledger as OwnerTransaction
 * records with:
 *
 * - direction = credit
 * - category = owner_deposit
 *
 * They are deliberately separate from tenant Payment records because
 * owner money does not belong to a Lease and may be received even when
 * the Owner has no active tenancy.
 */
class OwnerDepositReceiptDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for an Owner deposit receipt.
     *
     * Only genuine incoming Owner deposits may produce this document.
     */
    public function generate(
        OwnerTransaction $transaction
    ): string {
        $this->ensureOwnerDeposit(
            $transaction
        );

        $transaction->loadMissing([
            'ownerAccount.party',
            'building',
            'unit',
        ]);

        return Pdf::loadView(
            'documents.owner-deposit-receipt',
            [
                'transaction' => $transaction,

                'formatter' => $this->formatter,
                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),

                /*
                 * OwnerAccount balances are derived from the ledger.
                 *
                 * The current balance is useful operational information,
                 * but the receipt primarily confirms the deposit itself.
                 */
                'ownerBalance' => $transaction
                    ->ownerAccount
                    ->balance(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Return the customer-facing receipt filename.
     */
    public function filename(
        OwnerTransaction $transaction
    ): string {
        $this->ensureOwnerDeposit(
            $transaction
        );

        return sprintf(
            'Patrimoine-Owner-Deposit-Receipt-%06d.pdf',
            $transaction->id
        );
    }

    /**
     * Prevent non-deposit ledger entries from being rendered as receipts.
     */
    private function ensureOwnerDeposit(
        OwnerTransaction $transaction
    ): void {
        if (
            $transaction->direction !== 'credit'
            || $transaction->category !== 'owner_deposit'
        ) {
            throw new RuntimeException(
                __('business.owner.deposit_receipt_only')
            );
        }
    }
}
