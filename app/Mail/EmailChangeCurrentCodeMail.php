<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Step 2 of a sign-in email change: the code the CURRENT mailbox must
 * answer, naming the proposed replacement so the reader sees exactly
 * what they are authorising — and can raise the alarm if they never
 * asked.
 *
 * Account-security email is part of authentication: it is never blocked
 * or counted by licensing email quotas.
 */
class EmailChangeCurrentCodeMail extends Mailable
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
            subject: __('emails.email_change_current.subject', [
                'code' => $this->code,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-current-code'
        );
    }
}
