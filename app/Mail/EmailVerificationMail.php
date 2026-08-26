<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ask a newly signed-up user to verify their email address.
 *
 * Sign-in remains impossible until the verification link is used, so
 * this mail is part of authentication and is never blocked or counted
 * by licensing email quotas.
 */
class EmailVerificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
        public string $organisationName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.email_verification.subject')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification',
            with: [
                'organisationName' => $this->organisationName,
            ],
        );
    }
}
