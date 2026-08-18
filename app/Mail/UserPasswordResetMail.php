<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Deliver Patrimoine's secure 24-hour password reset link.
 */
class UserPasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl,
        public string $organisationName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.password_reset.subject', [
                'organisation' => $this->organisationName,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-password-reset'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
