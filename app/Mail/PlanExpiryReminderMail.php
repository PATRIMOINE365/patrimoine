<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Warn a customer organisation's administrators that their trial or
 * licence ends soon and what changes on the Free plan.
 *
 * Platform service mail: never counted against email allowances.
 */
class PlanExpiryReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $kind  trial|license
     * @param  int  $daysLeft  7 or 1
     */
    public function __construct(
        public User $user,
        public string $organisationName,
        public string $kind,
        public string $plan,
        public string $endsOn,
        public int $daysLeft
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.plan_expiry.subject', [
                'days' => $this->daysLeft,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expiry-reminder',
            with: [
                'organisationName' => $this->organisationName,
            ],
        );
    }
}
