<?php

namespace App\Mail;

use App\Models\Party;
use App\Models\RentIncrement;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email notifying a tenant of an approved future rent increment.
 *
 * Patrimoine sends this notification approximately three calendar months
 * before the scheduled rent increment becomes effective.
 *
 * The RentIncrement record preserves the contractual rent-change snapshot,
 * while the related Lease provides Tenant, Unit and Building context.
 */
class RentIncrementNoticeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public RentIncrement $rentIncrement,
        public ApplicationPresentationFormatter $formatter,
        public ?Party $managingOrganisation = null
    ) {}

    /**
     * Define the tenant-facing email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.rent_increment.subject', [
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
            view: 'emails.rent-increment-notice'
        );
    }

    /**
     * Rent-increment notices currently require no attachment.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Resolve the Managing Organisation name displayed to the tenant.
     */
    private function organisationName(): string
    {
        return $this->managingOrganisation?->legal_name
            ?? $this->managingOrganisation?->name
            ?? 'Patrimoine';
    }
}
