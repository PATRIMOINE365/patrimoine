<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Deliver the six-digit sign-in code for one MFA challenge.
 *
 * Sign-in and MFA email is part of authentication: it is never blocked
 * or counted by licensing email quotas.
 */
class MfaCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.mfa_code.subject', [
                'code' => $this->code,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mfa-code'
        );
    }
}
