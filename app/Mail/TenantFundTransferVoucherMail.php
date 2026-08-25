<?php

namespace App\Mail;

use App\Models\Party;
use App\Models\TenantFundTransaction;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * V1.0.8: email carrying a tenant fund Transfer voucher PDF.
 *
 * $debitTransaction is the voucher-carrying debit leg; $creditTransaction
 * is its counterpart, used to present the destination fund.
 */
class TenantFundTransferVoucherMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TenantFundTransaction $debitTransaction,
        public TenantFundTransaction $creditTransaction,
        public string $pdfContents,
        public string $pdfFilename,
        public ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {}

    /**
     * Define the voucher email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.transfer_voucher.subject', [
                'number' => (string) $this->debitTransaction->reference,
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
            view: 'emails.transfer-voucher'
        );
    }

    /**
     * Attach the generated Transfer voucher PDF.
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
