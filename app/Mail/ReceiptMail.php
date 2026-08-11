<?php

namespace App\Mail;

use App\Models\Party;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email used to send a receipt after tenant Payment.
 */
class ReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $pdfContents,
        public string $pdfFilename,
        public ?Party $managingOrganisation = null
    ) {
    }

    /**
     * Define the receipt email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Payment Receipt RCT-%06d - %s',
                $this->payment->id,
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
            view: 'emails.receipt'
        );
    }

    /**
     * Attach the generated receipt PDF.
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
