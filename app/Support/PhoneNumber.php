<?php

namespace App\Support;

/**
 * Telephone numbers, stored the one way a machine can dial them.
 *
 * V1.0.30 splits every phone field into a country and a national number.
 * What lands in the database is the two joined in E.164 — a plus sign and
 * nothing but digits — because that is the form WhatsApp, SMS gateways and
 * any future one-time-code delivery all require. The country is kept beside
 * it so the flag can be shown again exactly as it was chosen, which a
 * shared calling code like +1 could never tell us on its own.
 *
 * Numbers recorded before V1.0.30 are free text and stay as they were
 * typed. Everything here reads them without complaint and returns them
 * untouched; they are normalised the next time someone edits the record.
 */
class PhoneNumber
{
    /**
     * The longest national number E.164 allows, excluding the country code.
     */
    private const MAX_DIGITS = 15;

    /**
     * The shortest number worth accepting, including the country code.
     *
     * Saint Helena and Niue subscribers really do have four national
     * digits, so this is deliberately low rather than a guess at what a
     * "normal" number looks like.
     */
    private const MIN_DIGITS = 6;

    /**
     * ISO 3166-1 alpha-2 → ITU calling code.
     *
     * @return array<string, int>
     */
    public static function diallingCodes(): array
    {
        return config('countries.dialling_codes', []);
    }

    /**
     * Whether a country is one we can dial.
     */
    public static function knows(?string $country): bool
    {
        return $country !== null
            && array_key_exists(
                strtoupper($country),
                static::diallingCodes()
            );
    }

    /**
     * The calling code for a country, without its plus sign.
     */
    public static function codeFor(?string $country): ?int
    {
        if (! static::knows($country)) {
            return null;
        }

        return static::diallingCodes()[strtoupper($country)];
    }

    /**
     * Whether a value is already in E.164.
     */
    public static function isE164(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (preg_match('/^\+[1-9][0-9]{'.(static::MIN_DIGITS - 1).','.(static::MAX_DIGITS - 1).'}$/', $value) !== 1) {
            return false;
        }

        return static::countryFor($value) !== null;
    }

    /**
     * Join a country and a national number into one dialable number.
     *
     * Anything the caller spaced, bracketed or dashed is dropped, as is a
     * trunk prefix: 024 123 4567 in Ghana is +233 24 123 4567, never
     * +233 024 123 4567. Italy is the exception — a Rome landline really
     * is +39 06 …, and San Marino and the Vatican number the same way.
     */
    public static function compose(?string $country, ?string $national): ?string
    {
        $code = static::codeFor($country);

        if ($code === null) {
            return null;
        }

        $digits = preg_replace(
            '/[^0-9]/',
            '',
            (string) $national
        );

        if (! static::keepsTrunkZero($country)) {
            $digits = ltrim($digits, '0');
        }

        if ($digits === '') {
            return null;
        }

        return '+'.$code.$digits;
    }

    /**
     * Whether a country's leading zero is part of the number itself.
     */
    public static function keepsTrunkZero(?string $country): bool
    {
        return in_array(
            strtoupper((string) $country),
            config('countries.keeps_trunk_zero', []),
            true
        );
    }

    /**
     * The national part of a stored number, without its calling code.
     *
     * A number from before V1.0.30 has no calling code to remove, so it
     * comes back as it was stored.
     */
    public static function national(?string $stored, ?string $country = null): ?string
    {
        if ($stored === null || $stored === '') {
            return $stored;
        }

        $code = static::codeFor($country)
            ?? static::codeFor(static::countryFor($stored));

        if ($code === null || ! str_starts_with($stored, '+'.$code)) {
            return $stored;
        }

        return substr(
            $stored,
            strlen('+'.$code)
        );
    }

    /**
     * The country a stored number belongs to, read from its calling code.
     *
     * Longest match wins, so +1 is only reached once +1876 and the rest
     * have been ruled out. Where a code is genuinely shared — +1 across
     * North America, +7 across Russia and Kazakhstan — the answer is the
     * country configured as that code's default, and this is exactly why
     * the chosen country is stored rather than inferred.
     */
    public static function countryFor(?string $stored): ?string
    {
        if ($stored === null || ! str_starts_with($stored, '+')) {
            return null;
        }

        $digits = substr($stored, 1);

        $preferred = config('countries.preferred_for_code', []);

        $byCode = [];

        foreach (static::diallingCodes() as $iso => $code) {
            $byCode[$code] ??= $iso;
        }

        for ($length = 4; $length >= 1; $length--) {
            $candidate = (int) substr($digits, 0, $length);

            if ((string) $candidate !== substr($digits, 0, $length)) {
                continue;
            }

            if (isset($preferred[$candidate])) {
                return $preferred[$candidate];
            }

            if (isset($byCode[$candidate])) {
                return $byCode[$candidate];
            }
        }

        return null;
    }

    /**
     * A stored number, spaced for a person to read.
     *
     * Only the calling code is separated. Grouping the rest would mean
     * claiming to know each country's own convention, and getting that
     * wrong on a customer's invoice is worse than not grouping at all.
     */
    public static function display(?string $stored, ?string $country = null): ?string
    {
        if ($stored === null || $stored === '') {
            return $stored;
        }

        $code = static::codeFor($country)
            ?? static::codeFor(static::countryFor($stored));

        if ($code === null || ! str_starts_with($stored, '+'.$code)) {
            return $stored;
        }

        $national = substr(
            $stored,
            strlen('+'.$code)
        );

        return '+'.$code.' '.$national;
    }
}
