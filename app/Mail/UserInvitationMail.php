<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invite a Patrimoine application User to establish their own password.
 */
class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $invitationUrl,
        public string $organisationName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.user_invitation.subject', [
                'organisation' => $this->organisationName,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
