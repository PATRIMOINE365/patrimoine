<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the OLD mailbox after a sign-in email change completed —
 * whether through the three-step flow or by platform support.
 *
 * The old address is the one person guaranteed to be the account's
 * previous owner, so it is the address that must hear the account moved
 * — with instructions for reaching support if the change was never
 * theirs.
 */
class EmailChangeCompletedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $previousEmail,
        public string $newEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.email_change_completed.subject')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-completed'
        );
    }
}
