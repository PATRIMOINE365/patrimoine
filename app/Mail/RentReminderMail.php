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
 * Email reminder for an unpaid or partially paid rent Invoice.
 */
class RentReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfContents,
        public string $pdfFilename,
        public \App\Services\ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {
    }

    /**
     * Define a clear tenant-facing reminder subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Rent Reminder - Invoice %s - %s',
                $this->invoice->invoice_number,
                $this->organisationName()
            )
        );
    }

    /**
     * Define the reminder email body.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rent-reminder'
        );
    }

    /**
     * Include the relevant Invoice for convenience.
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
