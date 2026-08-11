<?php

namespace App\Services\Notifications;

use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Mail\RentReminderMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ApplicationIdentityService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\ReceiptDocumentService;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Central email-delivery service for Patrimoine.
 *
 * Responsibilities:
 *
 * - resolve the appropriate tenant recipient;
 * - resolve the configured managing-organisation identity;
 * - generate the appropriate financial PDF;
 * - construct the corresponding Mailable;
 * - deliver it through Laravel Mail.
 *
 * Controllers, payment services and scheduled commands should call this
 * service rather than constructing email messages themselves.
 */
class EmailDeliveryService
{
    public function __construct(
        private InvoiceDocumentService $invoiceDocuments,
        private ReceiptDocumentService $receiptDocuments,
        private ApplicationIdentityService $identity
    ) {
    }

    /**
     * Send or resend an Invoice to the tenant.
     */
    public function sendInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        $email = $this->invoiceRecipient($invoice);

        $contents = $this->invoiceDocuments
            ->generate($invoice);

        $filename = $this->invoiceDocuments
            ->filename($invoice);

        Mail::to($email)->send(
            new InvoiceMail(
                invoice: $invoice,
                pdfContents: $contents,
                pdfFilename: $filename,
                managingOrganisation:
                    $this->identity->managingOrganisation()
            )
        );
    }

    /**
     * Send a receipt for money actually received from a tenant.
     */
    public function sendReceipt(Payment $payment): void
    {
        $payment->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        $email = $this->paymentRecipient($payment);

        $contents = $this->receiptDocuments
            ->generate($payment);

        $filename = $this->receiptDocuments
            ->filename($payment);

        Mail::to($email)->send(
            new ReceiptMail(
                payment: $payment,
                pdfContents: $contents,
                pdfFilename: $filename,
                managingOrganisation:
                    $this->identity->managingOrganisation()
            )
        );
    }

    /**
     * Send a rent reminder with the current Invoice attached.
     */
    public function sendRentReminder(Invoice $invoice): void
    {
        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        if ($invoice->outstandingAmount() <= 0) {
            throw new RuntimeException(
                'A fully paid Invoice does not require a rent reminder.'
            );
        }

        $email = $this->invoiceRecipient($invoice);

        $contents = $this->invoiceDocuments
            ->generate($invoice);

        $filename = $this->invoiceDocuments
            ->filename($invoice);

        Mail::to($email)->send(
            new RentReminderMail(
                invoice: $invoice,
                pdfContents: $contents,
                pdfFilename: $filename,
                managingOrganisation:
                    $this->identity->managingOrganisation()
            )
        );
    }

    /**
     * Resolve the recipient of an Invoice-related message.
     */
    private function invoiceRecipient(
        Invoice $invoice
    ): string {
        $email = trim(
            (string) $invoice->lease->tenant->email
        );

        if ($email === '') {
            throw new RuntimeException(
                'Tenant does not have an email address.'
            );
        }

        return $email;
    }

    /**
     * Resolve the recipient of a Payment receipt.
     */
    private function paymentRecipient(
        Payment $payment
    ): string {
        $email = trim(
            (string) $payment->lease->tenant->email
        );

        if ($email === '') {
            throw new RuntimeException(
                'Tenant does not have an email address.'
            );
        }

        return $email;
    }
}
