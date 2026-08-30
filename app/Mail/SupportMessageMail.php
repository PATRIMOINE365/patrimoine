<?php

namespace App\Mail;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A customer's message, carried to support@patrimoine365.com.
 *
 * Internal mail, English only, like the signup alert: it is read by the
 * operating company, not by the customer who wrote it.
 *
 * Reply-To is the person who wrote it, so answering the mail answers
 * them — there is no ticket system behind this and pretending otherwise
 * would strand every reply.
 */
class SupportMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $author,
        public ?Organisation $organisation,
        public string $subjectLine,
        public string $body,
        public string $pageLanguage
    ) {}

    public function envelope(): Envelope
    {
        $organisation = $this->organisation?->name ?? 'Unknown organisation';

        return new Envelope(
            subject: '['.$organisation.'] '.$this->subjectLine,
            replyTo: [
                new Address($this->author->email, $this->author->name),
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-message'
        );
    }
}
