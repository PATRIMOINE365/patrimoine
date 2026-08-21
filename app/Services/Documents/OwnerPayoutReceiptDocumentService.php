<?php

namespace App\Services\Documents;

use App\Models\OwnerPayout;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates PDF receipts for money paid out to a property owner.
 *
 * OwnerPayout stores no dedicated number column, so the printed payout
 * number follows the id-based form used by the other receipt services:
 *
 *     POUT-000123
 */
class OwnerPayoutReceiptDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for an owner payout receipt.
     */
    public function generate(
        OwnerPayout $payout
    ): string {
        $payout->loadMissing([
            'ownerAccount.party',
            'allocations',
        ]);

        return Pdf::loadView(
            'documents.owner-payout-receipt',
            [
                'payout' => $payout,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),

                /*
                 * The remaining balance after the payout is useful
                 * operational information for the owner, mirroring the
                 * owner deposit receipt.
                 */
                'ownerBalance' => $payout
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
        OwnerPayout $payout
    ): string {
        return sprintf(
            'Patrimoine-Owner-Payout-Receipt-%06d.pdf',
            $payout->id
        );
    }
}
