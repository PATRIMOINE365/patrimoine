<?php

namespace Tests\Feature;

use App\Support\ErrorCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Every failure Patrimoine can show carries a code, and every code has
 * an explanation in both languages.
 *
 * The point of a code is that three things agree: what appears on
 * screen, what somebody reads out over the telephone, and what the
 * Error codes page says. These tests hold those three together.
 */
class ErrorCodeTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | The catalogue itself
    |--------------------------------------------------------------------------
    */

    public function test_every_code_is_well_formed_and_unique(): void
    {
        $codes = array_keys(ErrorCodes::all());

        $this->assertNotEmpty($codes);

        $this->assertSame(
            $codes,
            array_values(array_unique($codes)),
            'A code is used twice.'
        );

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^PM-[1-9][0-9]{3}$/',
                $code,
                "{$code} is not of the form PM-Fnnn."
            );
        }
    }

    public function test_every_code_can_be_explained_in_both_languages(): void
    {
        $missing = [];

        foreach (array_keys(ErrorCodes::all()) as $code) {
            foreach (['en', 'fr'] as $locale) {
                $text = ErrorCodes::text($code, $locale);

                if ($text === null) {
                    $missing[] = "{$locale}: {$code} has no entry";

                    continue;
                }

                foreach (['title', 'what', 'fix'] as $field) {
                    if (trim((string) ($text[$field] ?? '')) === '') {
                        $missing[] = "{$locale}: {$code} has no {$field}";
                    }
                }
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    public function test_every_code_says_who_can_act(): void
    {
        $allowed = ['fix_yourself', 'try_again', 'ask_admin', 'contact_us'];

        foreach (array_keys(ErrorCodes::all()) as $code) {
            $this->assertContains(
                ErrorCodes::severity($code),
                $allowed,
                "{$code} has no usable severity."
            );
        }
    }

    /**
     * A key that no longer resolves means a message was renamed and the
     * catalogue was left pointing at nothing.
     */
    public function test_catalogued_keys_still_resolve_to_a_message(): void
    {
        $dangling = [];

        foreach (ErrorCodes::all() as $code => $entry) {
            foreach ($entry['keys'] ?? [] as $key) {
                /*
                 * Browser-only keys live in translations.js and are
                 * matched in the browser; the server cannot resolve them.
                 */
                if (! str_contains($key, '.')) {
                    continue;
                }

                [$file] = explode('.', $key, 2);

                if (! in_array($file, ['business', 'api', 'validation', 'ui'], true)) {
                    continue;
                }

                if (__($key) === $key) {
                    $dangling[] = "{$code} → {$key}";
                }
            }
        }

        $this->assertSame(
            [],
            $dangling,
            "Catalogued message keys that no longer exist:\n".implode("\n", $dangling)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recognising a message
    |--------------------------------------------------------------------------
    */

    public function test_a_business_rule_is_traced_back_to_its_code(): void
    {
        $code = ErrorCodes::forMessage(
            __('business.owner.payout_positive', [], 'en'),
            'en'
        );

        $this->assertNotNull($code);
        $this->assertSame(4, ErrorCodes::family($code));
    }

    public function test_a_validation_message_is_traced_back_through_its_placeholder(): void
    {
        /*
         * By the time the message is rendered the placeholder is gone and
         * the field name is in its place, so the catalogue has to match
         * it by shape.
         */
        $code = ErrorCodes::forMessage(
            'The rent amount field is required.',
            'en'
        );

        $this->assertNotNull($code);
        $this->assertSame(2, ErrorCodes::family($code));
    }

    public function test_a_sentence_from_nowhere_gets_no_code(): void
    {
        $this->assertNull(
            ErrorCodes::forMessage('Something nobody ever wrote.', 'en')
        );
    }

    public function test_the_same_message_resolves_in_french(): void
    {
        $french = __('business.owner.payout_positive', [], 'fr');

        $this->assertNotSame(
            'business.owner.payout_positive',
            $french,
            'The French message is missing, so this test proves nothing.'
        );

        $this->assertNotNull(ErrorCodes::forMessage($french, 'fr'));
    }

    /*
    |--------------------------------------------------------------------------
    | What the application answers
    |--------------------------------------------------------------------------
    */

    public function test_an_api_refusal_carries_its_code(): void
    {
        $this->authenticateApiUser('administrator');

        $response = $this->postJson('/api/parties', [
            'type' => 'person',
            /* No surname, no phone, no email: the form will refuse. */
        ]);

        $response->assertStatus(422);

        $code = $response->json('code');

        $this->assertNotNull($code, 'A refused request came back with no code.');
        $this->assertSame(2, ErrorCodes::family($code));
    }

    public function test_a_missing_page_answers_with_the_page_not_found_code(): void
    {
        $response = $this->getJson('/api/no-such-endpoint');

        $response->assertStatus(404);
        $response->assertJsonPath('code', 'PM-9901');
    }

    /*
    |--------------------------------------------------------------------------
    | The pages
    |--------------------------------------------------------------------------
    */

    public function test_the_reference_page_is_public_and_lists_the_catalogue(): void
    {
        $response = $this->get('/errors');

        $response->assertOk();
        $response->assertSee('PM-9904', false);
        $response->assertSee(__('ui.errors.what_to_do'), false);
    }

    public function test_the_reference_page_opens_at_one_code(): void
    {
        $response = $this->get('/errors/PM-9904');

        $response->assertOk();
        $response->assertSee(
            ErrorCodes::text('PM-9904', 'en')['title'],
            false
        );
    }

    public function test_an_unknown_code_says_so_rather_than_dead_ending(): void
    {
        $response = $this->get('/errors/PM-9999');

        $response->assertOk();
        $response->assertSee('PM-9999', false);
    }

    public function test_the_reference_page_reads_in_french_when_asked(): void
    {
        $response = $this->get('/errors?lang=fr');

        $response->assertOk();
        $response->assertSee(__('ui.errors.what_to_do', [], 'fr'), false);
    }

    /**
     * The telephone number is on the failures nobody else can resolve,
     * and nowhere else: a number on every message is a number nobody
     * reads.
     */
    public function test_support_details_appear_only_where_they_belong(): void
    {
        $ours = null;
        $theirs = null;

        foreach (array_keys(ErrorCodes::all()) as $code) {
            $severity = ErrorCodes::severity($code);

            if ($severity === 'contact_us' && $ours === null) {
                $ours = $code;
            }

            if ($severity === 'fix_yourself' && $theirs === null) {
                $theirs = $code;
            }
        }

        $this->assertNotNull($ours);
        $this->assertNotNull($theirs);

        $phone = config('legal.support.phone_display');

        /*
         * The page as a whole carries the contact block, so the check is
         * on the card: the one for our failure carries the number, the
         * one for a rule the reader can satisfy does not.
         */
        $page = $this->get('/errors')->getContent();

        $card = fn (string $code): string => $this->cardFor($page, $code);

        $this->assertStringContainsString($phone, $card($ours));
        $this->assertStringNotContainsString($phone, $card($theirs));
    }

    /**
     * The Error codes tab inside Patrimoine reads the same catalogue.
     */
    public function test_the_in_app_catalogue_is_available_to_every_role(): void
    {
        foreach (['administrator', 'property_manager', 'viewer'] as $role) {
            $this->authenticateApiUser($role);

            $response = $this->getJson('/api/error-codes');

            $response->assertOk();
            $response->assertJsonStructure([
                'families' => [['family', 'name']],
                'codes' => [['code', 'family', 'severity', 'title', 'what', 'fix']],
                'contact' => ['phone', 'whatsapp', 'email'],
            ]);
        }
    }

    /**
     * Pull one code's card out of the rendered page.
     */
    private function cardFor(string $page, string $code): string
    {
        $start = strpos($page, 'data-error-code="'.$code.'"');

        $this->assertNotFalse($start, "{$code} is not on the page.");

        $end = strpos($page, '</article>', $start);

        return substr($page, $start, $end - $start);
    }
}
