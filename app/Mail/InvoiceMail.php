<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email used to deliver or resend a tenant Invoice.
 *
 * PDF generation happens before this mailable is created. The mailable
 * therefore concerns itself only with presentation and attachment.
 */
class InvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfContents,
        public string $pdfFilename,
        public ?Party $managingOrganisation = null
    ) {
    }

    /**
     * Define the email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Invoice %s - %s',
                $this->invoice->invoice_number,
                $this->organisationName()
            )
        );
    }

    /**
     * Define the email body.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice'
        );
    }

    /**
     * Attach the Invoice PDF directly from generated memory.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfContents,
                $this->pdfFilename
            )->withMime('application/pdf'),
        ];
    }

    /**
     * Resolve the business name shown to the recipient.
     */
    private function organisationName(): string
    {
        return $this->managingOrganisation?->legal_name
            ?? $this->managingOrganisation?->name
            ?? 'Patrimoine';
    }
}
