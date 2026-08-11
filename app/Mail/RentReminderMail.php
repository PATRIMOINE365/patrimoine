<?php

namespace App\Mail;

use App\Models\Invoice;
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
        public string $pdfFilename
    ) {
    }

    /**
     * Define a clear tenant-facing reminder subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Rent Reminder - Invoice %s',
                $this->invoice->invoice_number
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
}
