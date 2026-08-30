<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Weekly digest to billing@ of trials and licences ending within the
 * next 14 days.
 *
 * Internal mail; English only.
 *
 * @phpstan-type ExpiryRow array{organisation: string, kind: string, plan: string, ends_on: string}
 */
class PlatformExpiryDigestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<array<string, string>>  $rows
     */
    public function __construct(
        public array $rows
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Patrimoine 365: %d plan(s) expiring within 14 days',
                count($this->rows)
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-expiry-digest'
        );
    }
}
