<?php

namespace App\Mail;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tell Kality that a new organisation registered.
 *
 * Internal mail to hello@patrimoine365.com; English only.
 */
class SignupAlertMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Organisation $organisation,
        public User $administrator
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New signup: '.$this->organisation->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signup-alert'
        );
    }
}
