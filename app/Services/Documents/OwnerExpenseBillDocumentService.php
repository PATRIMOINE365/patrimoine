<?php

namespace App\Services\Documents;

use App\Models\OwnerExpenseBill;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates the itemized PDF for an owner expense bill.
 *
 * The bill lists every expense line (description + amount) charged
 * DIRECTLY to one owner in a single billing batch, together with the
 * batch total and the printed bill identity.
 */
class OwnerExpenseBillDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for an owner expense bill.
     */
    public function generate(
        OwnerExpenseBill $bill
    ): string {
        $bill->loadMissing([
            'ownerAccount.party',
            'expenses',
        ]);

        return Pdf::loadView(
            'documents.owner-expense-bill',
            [
                'bill' => $bill,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Return the customer-facing bill filename.
     */
    public function filename(
        OwnerExpenseBill $bill
    ): string {
        return sprintf(
            'Patrimoine-Owner-Expense-Bill-%s.pdf',
            $bill->bill_number
        );
    }
}
