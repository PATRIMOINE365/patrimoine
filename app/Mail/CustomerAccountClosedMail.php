<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tell Kality that a customer closed their own account.
 *
 * Internal mail to hello@patrimoine365.com; English only. Nothing here is
 * a model — by the time this is sent there is no organisation left to
 * load, so every fact is passed by value.
 */
class CustomerAccountClosedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $organisationName,
        public string $administratorName,
        public string $administratorEmail,
        public int $rowsDeleted
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account closed: '.$this->organisationName
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-account-closed'
        );
    }
}
