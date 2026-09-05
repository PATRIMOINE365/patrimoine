<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * V1.0.51: sent to a customer's administrators when platform staff
 * delete their organisation from the console.
 *
 * A self-closure has told its administrator since 1.0.50; a deletion
 * done on their behalf told nobody. The people whose data is gone are
 * the ones who must hear it, in writing, from the platform.
 *
 * Not queued: the organisation a job would bind to no longer exists.
 */
class OrganisationDeletedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $organisationName,
        public string $administratorName,
        public string $deletedOn
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.organisation_deleted.subject', [
                'organisation' => $this->organisationName,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organisation-deleted'
        );
    }
}
