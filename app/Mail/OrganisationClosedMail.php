<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * V1.0.50: sent to the administrator who closed their organisation.
 *
 * Kality has always been told that a customer left; the customer
 * themselves heard nothing, and closing an account is exactly the moment
 * a person wants written confirmation that it happened, when, and that
 * nothing of theirs remains.
 *
 * Deliberately NOT queued: the organisation that would have owned the
 * job is gone, and a mail this final is better sent in the request that
 * did the deleting than handed to a worker that has nothing left to
 * bind to.
 */
class OrganisationClosedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $organisationName,
        public string $administratorName,
        public string $closedOn
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.organisation_closed.subject', [
                'organisation' => $this->organisationName,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organisation-closed'
        );
    }
}
