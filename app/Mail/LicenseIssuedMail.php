<?php

namespace App\Mail;

use App\Models\License;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirm a newly issued licence to a customer organisation's
 * administrators.
 *
 * Platform service mail: never counted against email allowances.
 */
class LicenseIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $organisationName,
        public License $license
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.license_issued.subject', [
                'plan' => __('emails.plans.'.$this->license->plan),
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.license-issued',
            with: [
                'organisationName' => $this->organisationName,
            ],
        );
    }
}
