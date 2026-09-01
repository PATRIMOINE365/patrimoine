<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Step 3 of a sign-in email change: the code the PROPOSED mailbox must
 * answer before it becomes the account's address.
 *
 * Account-security email is part of authentication: it is never blocked
 * or counted by licensing email quotas.
 */
class EmailChangeProposedCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $proposedEmail,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.email_change_proposed.subject', [
                'code' => $this->code,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-proposed-code'
        );
    }
}
