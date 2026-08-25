<?php

namespace App\Mail;

use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * V1.0.8: email carrying an owner account transfer voucher PDF.
 */
class OwnerReserveTransferVoucherMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public OwnerTransaction $transaction,
        public string $pdfContents,
        public string $pdfFilename,
        public ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.owner_reserve_transfer.subject', [
                'number' => (string) $this->transaction->reference,
                'organisation' => $this->organisationName(),
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner-reserve-transfer'
        );
    }

    /**
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

    private function organisationName(): string
    {
        return $this->managingOrganisation?->legal_name
            ?? $this->managingOrganisation?->name
            ?? 'Patrimoine';
    }
}
