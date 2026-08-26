<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use App\Support\HtmlToPlainText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * V1.0.17: every email ships multipart/alternative. HTML-only mail is
 * scored more harshly by spam and phishing filters — one of the signals
 * behind the Microsoft 365 quarantine of our verification email.
 */
class PlainTextEmailPartTest extends TestCase
{
    use RefreshDatabase;

    public function test_outgoing_mail_gains_a_plain_text_alternative(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.test',
        ]);

        Mail::to($user->email)->send(
            new EmailVerificationMail(
                user: $user,
                verificationUrl: 'https://app.patrimoine365.com/verify-email?token=abc123',
                organisationName: 'Example Lettings'
            )
        );

        /*
         * The array transport keeps the fully built message, so the
         * listener's work is observable exactly as a provider sees it.
         */
        $messages = Mail::getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $email = $messages->first()->getOriginalMessage();

        $sentText = $email->getTextBody();

        $this->assertNotNull(
            $sentText,
            'Outgoing mail must carry a plain-text alternative.'
        );

        $this->assertNotNull($email->getHtmlBody());

        $this->assertStringContainsString(
            'https://app.patrimoine365.com/verify-email?token=abc123',
            $sentText
        );

        $this->assertStringContainsString('Example Lettings', $sentText);

        $this->assertStringNotContainsString('<a ', $sentText);
        $this->assertStringNotContainsString('<td', $sentText);
        $this->assertStringNotContainsString('style=', $sentText);
    }

    public function test_links_keep_their_destination_in_the_text_part(): void
    {
        $text = HtmlToPlainText::convert(
            '<p>Hello</p><a href="https://example.test/go">Click here</a>'
        );

        $this->assertSame(
            "Hello\nClick here (https://example.test/go)",
            $text
        );
    }

    public function test_styles_scripts_and_markup_are_stripped(): void
    {
        $text = HtmlToPlainText::convert(
            '<style>.a{color:red}</style><script>alert(1)</script>'
            .'<h1>Title</h1><p>Body&nbsp;text &amp; more</p>'
        );

        $this->assertStringNotContainsString('color:red', $text);
        $this->assertStringNotContainsString('alert(1)', $text);
        $this->assertStringContainsString('Title', $text);
        $this->assertStringContainsString('Body text & more', $text);
    }

    public function test_image_alt_text_survives_and_bare_links_are_not_duplicated(): void
    {
        $text = HtmlToPlainText::convert(
            '<img src="https://patrimoine365.com/icon-192.png" alt="Patrimoine 365">'
            .'<a href="https://patrimoine365.com/">https://patrimoine365.com/</a>'
        );

        $this->assertStringContainsString('Patrimoine 365', $text);
        $this->assertStringNotContainsString('icon-192.png', $text);
        $this->assertSame(
            1,
            substr_count($text, 'https://patrimoine365.com/')
        );
    }
}
