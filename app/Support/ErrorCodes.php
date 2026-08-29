<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * The error catalogue, as the application reads it.
 *
 * Every failure Patrimoine can show carries a code, so that what appears
 * on screen, what somebody reads out over the telephone and what the
 * Error codes page explains are one and the same thing.
 *
 * Two lookups matter. `forKey()` is exact and is used wherever the code
 * raising the error knows which message it is about to show. `forMessage()`
 * works backwards from the rendered sentence, which is what the exception
 * handler has to work with: by then the key is gone and only the
 * translated text remains.
 *
 * The backwards lookup is built once per request and per language. Most
 * messages match by their exact text; the ones carrying placeholders —
 * "The :attribute field is required." — are matched by pattern, because
 * the sentence that reached the customer has the field name in it.
 */
class ErrorCodes
{
    /**
     * Rendered message → code, per language.
     *
     * @var array<string, array<string, string>>
     */
    private static array $exact = [];

    /**
     * [pattern, code] for messages that carry placeholders, per language.
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    private static array $patterns = [];

    /**
     * The HTTP failures that have no message of their own.
     *
     * @var array<int, string>
     */
    private const STATUS_CODES = [
        404 => 'PM-9901',
        419 => 'PM-9902',
        429 => 'PM-9903',
        500 => 'PM-9904',
        503 => 'PM-9905',
    ];

    /**
     * Every code with its family, severity and message keys.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('error_codes.codes', []);
    }

    /**
     * The code a known message key carries.
     */
    public static function forKey(string $key): ?string
    {
        foreach (self::all() as $code => $entry) {
            if (in_array($key, $entry['keys'] ?? [], true)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * The code for an already-rendered message.
     *
     * Returns null when the sentence belongs to no catalogued error,
     * which is the honest answer: inventing a code for an unrecognised
     * message would send somebody to a page that cannot explain it.
     */
    public static function forMessage(?string $message, ?string $locale = null): ?string
    {
        if ($message === null || trim($message) === '') {
            return null;
        }

        $locale ??= app()->getLocale();

        self::buildLookup($locale);

        $needle = self::normalise($message);

        if (isset(self::$exact[$locale][$needle])) {
            return self::$exact[$locale][$needle];
        }

        foreach (self::$patterns[$locale] as [$pattern, $code]) {
            if (preg_match($pattern, $message) === 1) {
                return $code;
            }
        }

        return null;
    }

    /**
     * The code for a rendered error response.
     *
     * The message is tried first, then the first validation message —
     * "the rent must be a number" is more useful than "some input was
     * invalid" — and finally the status, for requests that failed with
     * no message of their own.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function forResponse(array $payload, int $status): ?string
    {
        $code = self::forMessage(
            is_string($payload['message'] ?? null)
                ? $payload['message']
                : null
        );

        if ($code !== null) {
            return $code;
        }

        foreach ((array) ($payload['errors'] ?? []) as $messages) {
            foreach ((array) $messages as $message) {
                $code = self::forMessage(
                    is_string($message) ? $message : null
                );

                if ($code !== null) {
                    return $code;
                }
            }
        }

        return self::forStatus($status);
    }

    /**
     * The code for an HTTP status that failed without a message.
     */
    public static function forStatus(int $status): ?string
    {
        return self::STATUS_CODES[$status] ?? null;
    }

    /**
     * What the person saw, why it happened, and what to do about it.
     *
     * @return array{title: string, what: string, fix: string}|null
     */
    public static function text(string $code, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();

        $entry = Lang::get("errors.{$code}", [], $locale);

        return is_array($entry) ? $entry : null;
    }

    /**
     * Who can act on this: fix_yourself, try_again, ask_admin, contact_us.
     */
    public static function severity(string $code): ?string
    {
        return self::all()[$code]['severity'] ?? null;
    }

    public static function family(string $code): ?int
    {
        return self::all()[$code]['family'] ?? null;
    }

    /**
     * The name of a family, for grouping the reference page.
     */
    public static function familyName(int $family): ?string
    {
        return config("error_codes.families.{$family}");
    }

    /**
     * Does this code mean somebody should hear from us?
     */
    public static function needsSupport(string $code): bool
    {
        return in_array(
            self::severity($code),
            ['contact_us', 'ask_admin'],
            true
        );
    }

    /**
     * How to reach us, for the codes nobody else can resolve.
     *
     * @return array{phone: string, whatsapp: string, email: string}
     */
    public static function contact(): array
    {
        return [
            'phone' => config('legal.support.phone'),
            'whatsapp' => config('legal.support.whatsapp'),
            'email' => config('legal.mailboxes.support'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The backwards lookup
    |--------------------------------------------------------------------------
    */

    /**
     * Render every catalogued message once, so a sentence can be traced
     * back to the code that owns it.
     */
    private static function buildLookup(string $locale): void
    {
        if (isset(self::$exact[$locale])) {
            return;
        }

        self::$exact[$locale] = [];
        self::$patterns[$locale] = [];

        foreach (self::all() as $code => $entry) {
            foreach ($entry['keys'] ?? [] as $key) {
                $line = Lang::get($key, [], $locale);

                /*
                 * A key the server cannot resolve belongs to the browser
                 * catalogue. Those are matched in the browser instead.
                 */
                if (! is_string($line) || $line === $key) {
                    continue;
                }

                if (str_contains($line, ':')) {
                    self::$patterns[$locale][] = [
                        self::patternFor($line),
                        $code,
                    ];

                    continue;
                }

                self::$exact[$locale][self::normalise($line)] ??= $code;
            }
        }
    }

    /**
     * Turn "The :attribute field is required." into a pattern that still
     * matches once Laravel has filled the placeholder in.
     */
    private static function patternFor(string $line): string
    {
        $quoted = preg_quote($line, '/');

        /*
         * preg_quote escapes the colon, so a placeholder arrives here as
         * "\\:attribute". Both forms have to go, or the pattern ends up
         * looking for a literal run of dots where the field name should be.
         */
        $pattern = preg_replace('/\\\\?:[a-zA-Z_]+/', '.+?', $quoted);

        return '/^'.$pattern.'$/u';
    }

    private static function normalise(string $message): string
    {
        return mb_strtolower(
            trim(
                preg_replace('/\s+/u', ' ', $message)
            )
        );
    }
}
