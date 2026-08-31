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
        private ApplicationPresentationFormatter $formatter,
        private OwnerPayoutBreakdownService $breakdown
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

                /*
                 * The workings: what came in since the owner last
                 * collected, what was taken off it, and how that reaches
                 * the figure above. Null only when the payout has no
                 * account or party to report on, which cannot happen
                 * through the application.
                 */
                'breakdown' => $this->breakdown->forPayout($payout),
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
