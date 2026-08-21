<?php

namespace App\Mail;

use App\Models\OwnerExpenseBill;
use App\Models\Party;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email used to send an itemized owner expense bill to the billed owner.
 */
class OwnerExpenseBillMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public OwnerExpenseBill $bill,
        public string $pdfContents,
        public string $pdfFilename,
        public ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {}

    /**
     * Define the bill email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.owner_expense_bill.subject', [
                'number' => $this->bill->bill_number,
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
            view: 'emails.owner-expense-bill'
        );
    }

    /**
     * Attach the generated itemized bill PDF.
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
