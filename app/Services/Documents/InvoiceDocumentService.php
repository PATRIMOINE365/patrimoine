<?php

namespace App\Services\Documents;

use App\Models\Invoice;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates customer-facing PDF Invoices.
 *
 * This service deliberately returns PDF bytes rather than storing files.
 * The caller may therefore:
 *
 * - stream the document to a browser;
 * - attach it to an email;
 * - archive it later;
 * - regenerate it when necessary.
 *
 * Invoice financial values are taken from the historical Invoice snapshot,
 * not recalculated from the current Lease configuration.
 */
class InvoiceDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate the PDF contents for an Invoice.
     */
    public function generate(Invoice $invoice): string
    {
        /*
         * Load all information required by the document in one predictable
         * object graph before rendering the Blade template.
         */
        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
            'paymentAllocations.payment',
            'tenantFundTransactions',
        ]);

        return Pdf::loadView(
            'documents.invoice',
            [
                'invoice' => $invoice,
                'formatter' => $this->formatter,
                'managingOrganisation' => $this->identity->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Return the customer-facing filename used for downloads and emails.
     */
    public function filename(Invoice $invoice): string
    {
        return sprintf(
            'Patrimoine-Invoice-%s.pdf',
            $this->safeFilenamePart($invoice->invoice_number)
        );
    }

    /**
     * Sanitize dynamic filename content.
     */
    private function safeFilenamePart(string $value): string
    {
        return preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            $value
        ) ?? 'invoice';
    }
}
