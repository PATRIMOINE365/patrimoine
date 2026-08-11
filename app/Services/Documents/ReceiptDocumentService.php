<?php

namespace App\Services\Documents;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates PDF receipts for recorded tenant Payments.
 *
 * A receipt represents cash actually received by Patrimoine. It therefore
 * follows the Payment record rather than an individual Invoice because one
 * Payment may settle several Invoices through FIFO allocation.
 */
class ReceiptDocumentService
{
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
