<?php

namespace App\Listeners;

use App\Support\HtmlToPlainText;
use Illuminate\Mail\Events\MessageSending;

/**
 * Give every outgoing email a plain-text alternative.
 *
 * Patrimoine's mailables render Blade HTML views. Adding the text part
 * here — once, centrally — means all sixteen of them ship proper
 * multipart/alternative mail without maintaining a parallel text
 * template for each, and any future mailable inherits it for free.
 *
 * Mailables that already provide their own text view are left untouched.
 */
class AttachPlainTextEmailPart
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;

        if ($message->getTextBody() !== null) {
            return;
        }

        $html = $message->getHtmlBody();

        if (! is_string($html) || trim($html) === '') {
            return;
        }

        $text = HtmlToPlainText::convert($html);

        if ($text === '') {
            return;
        }

        $message->text($text);
    }
}
