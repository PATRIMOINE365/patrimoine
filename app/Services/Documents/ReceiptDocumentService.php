<?php

namespace App\Services\Documents;

use App\Models\Payment;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates PDF receipts for recorded tenant Payments.
 *
 * A receipt represents cash actually received by the managing organisation.
 * It therefore follows the Payment record rather than an individual Invoice
 * because one Payment may settle several Invoices through FIFO allocation.
 */
class ReceiptDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {
    }

    /**
     * Generate the PDF contents for a Payment receipt.
     */
    public function generate(Payment $payment): string
    {
        $payment->loadMissing([
            'lease.tenant',
            'lease.unit.building',
            'allocations.invoice',
        ]);

        return Pdf::loadView(
            'documents.receipt',
            [
                'payment' => $payment,
                'formatter' => $this->formatter,
                'managingOrganisation' =>
                    $this->identity->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Return the customer-facing receipt filename.
     */
    public function filename(Payment $payment): string
    {
        return sprintf(
            'Patrimoine-Receipt-%06d.pdf',
            $payment->id
        );
    }
}
