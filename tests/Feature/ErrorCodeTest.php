<?php

namespace Tests\Feature;

use App\Support\ErrorCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
        /*
         * A catalogue key has to resolve SOMEWHERE — either as a server
         * translation or in the browser catalogue, because a code can be
         * raised from either side.
         *
         * This used to allow only four groups through, named by hand:
         * business, api, validation and ui. Three more real language files
         * have been added since (financial_journal, activity_log, reports)
         * and the list was never extended, so thirteen keys pointing at
         * messages that do not exist sat here undetected. Asking "does it
         * resolve anywhere" needs no list to keep up to date.
         */
        $browser = File::get(resource_path('js/translations.js'));

        $dangling = [];

        foreach (ErrorCodes::all() as $code => $entry) {
            foreach ($entry['keys'] ?? [] as $key) {
                if (__($key) !== $key) {
                    continue;
                }

                // Present in both locale halves of the browser catalogue.
                if (substr_count($browser, "'{$key}':") >= 2) {
                    continue;
                }

                $dangling[] = "{$code} → {$key}";
            }
        }

        $this->assertSame(
            [],
            $dangling,
            "Catalogued message keys that no longer exist:\n".implode("\n", $dangling)
        );
    }

    /**
     * The browser half of the catalogue must agree with the catalogue.
     *
     * error-codes.js said it was generated from config/error_codes.php for
     * a long time while no generator existed. It fell fifteen codes behind,
     * and three French sentences were written into it as literal "’"
     * escape sequences, so they could never match anything a customer
     * actually read: those messages reached people with no code attached
     * and support had nothing to ask for.
     *
     * scripts/generate-error-codes.mjs writes that file now. This checks the
     * outcome rather than the bytes — every browser message the catalogue
     * claims must be recognisable — so it fails whether somebody edits the
     * generated file by hand or forgets to re-run the generator.
     */
    public function test_every_browser_message_is_recognisable(): void
    {
        $browser = File::get(resource_path('js/translations.js'));
        $generated = File::get(resource_path('js/error-codes.js'));

        $unrecognised = [];

        foreach (ErrorCodes::all() as $code => $entry) {
            foreach ($entry['keys'] ?? [] as $key) {
                foreach ($this->browserMessagesFor($browser, $key) as $message) {
                    /*
                     * A message carrying a value cannot be looked up once
                     * the value is filled in; the generator emits those as
                     * patterns instead, which are checked below.
                     */
                    if (preg_match('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', $message) === 1) {
                        continue;
                    }

                    $needle = json_encode(
                        $this->flatten($message),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );

                    if (! str_contains($generated, $needle.": '{$code}'")) {
                        $unrecognised[] = "{$code} → {$key}: {$message}";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $unrecognised,
            "Browser messages the browser could not attach a code to.\n"
            ."Run: node scripts/generate-error-codes.mjs\n\n"
            .implode("\n", $unrecognised)
        );
    }

    /**
     * No two codes may share a sentence.
     *
     * The browser matches on the sentence alone, so two codes wording a
     * refusal identically leave one of them unreachable for ever — and in
     * a flat map the loser is decided by write order, silently. This once
     * happened in French only: PM-4053 and PM-4018 both read "Le paiement
     * dépasse le montant restant dû de la facture."
     */
    public function test_no_two_codes_share_a_sentence(): void
    {
        $browser = File::get(resource_path('js/translations.js'));

        $owners = [];
        $shared = [];

        foreach (ErrorCodes::all() as $code => $entry) {
            foreach ($entry['keys'] ?? [] as $key) {
                foreach ($this->browserMessagesFor($browser, $key) as $message) {
                    $text = $this->flatten($message);

                    if (isset($owners[$text]) && $owners[$text] !== $code) {
                        $shared[] = "{$owners[$text]} and {$code}: \"{$message}\"";

                        continue;
                    }

                    $owners[$text] = $code;
                }
            }
        }

        $this->assertSame(
            [],
            array_unique($shared),
            "Two codes cannot word a refusal identically — one could never be shown:\n"
            .implode("\n", array_unique($shared))
        );
    }

    /**
     * Every value translations.js holds for one key, in either language.
     *
     * @return array<int, string>
     */
    private function browserMessagesFor(string $catalogue, string $key): array
    {
        // The value may sit on the same line as the key or the next one.
        preg_match_all(
            "/'".preg_quote($key, '/')."':\s*'((?:[^'\\\\]|\\\\.)*)'/",
            $catalogue,
            $matches
        );

        return array_map(
            static function (string $raw): string {
                $value = str_replace(["\\'", '\\\\'], ["'", '\\'], $raw);

                /*
                 * Parts of translations.js write accented French as \uXXXX
                 * escapes. JavaScript decodes those when the module loads,
                 * so the sentence a customer reads is the decoded one —
                 * compare against that, not against the source bytes.
                 */
                return (string) preg_replace_callback(
                    '/\\\\u([0-9a-fA-F]{4})/',
                    static fn (array $m): string => mb_chr((int) hexdec($m[1]), 'UTF-8'),
                    $value
                );
            },
            $matches[1]
        );
    }

    /**
     * The same normalisation errorCodeForMessage() applies before looking up.
     */
    private function flatten(string $message): string
    {
        return mb_strtolower(
            trim((string) preg_replace('/\s+/u', ' ', $message))
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

    /**
     * A record that cannot be found does not name the class behind it.
     *
     * Laravel renders a missing model with the ORM's own sentence — "No
     * query results for model [App\Models\Building] 1" — which told
     * whoever asked what our internal classes are called, said it in
     * English whatever language the organisation reads in, and belonged to
     * no catalogue entry.
     *
     * This is also the answer somebody gets reaching for another
     * organisation's record. That answer is deliberately 404 rather than
     * 403, because 403 would confirm the record exists — and it must not
     * then leak what kind of record it was.
     */
    public function test_a_missing_record_does_not_name_the_model(): void
    {
        $this->authenticateApiUser();

        $response = $this->getJson('/api/buildings/999999');

        $response->assertStatus(404);
        $response->assertJsonPath('code', 'PM-9901');

        $message = (string) $response->json('message');

        $this->assertStringNotContainsString('App\\Models', $message);
        $this->assertStringNotContainsString('No query results', $message);
        $this->assertSame(__('api.not_found'), $message);
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
