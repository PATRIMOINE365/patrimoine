<?php

namespace App\Mail;

use App\Models\Party;
use App\Models\Payment;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email used to send a receipt after tenant Payment.
 */
/*
 * NOT queued, and not by choice.
 *
 * This mailable carries the rendered PDF as raw bytes, and a queued job
 * is stored as JSON — binary does not survive it ("Malformed UTF-8
 * characters"). Base64 would survive it and would put a quarter of a
 * megabyte per reminder into the jobs table, which is not a trade worth
 * making on this box.
 *
 * Queueing this properly means the job carrying the record's id and
 * rendering the document itself, which is a change per mailable and per
 * call site. Until then it is sent inside the request, and the nightly
 * reminder run still waits on Resend once per document.
 */
class ReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $pdfContents,
        public string $pdfFilename,
        public ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {}

    /**
     * Define the receipt email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.receipt.subject', [
                'number' => sprintf('RCT-%06d', $this->payment->id),
                'organisation' => $this->organisationName(),
            ])
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
