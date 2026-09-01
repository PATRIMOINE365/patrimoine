<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Central source of truth for Patrimoine presentation settings.
 *
 * Language and currency belong to the Managing Organisation installation,
 * not to individual users or transactions.
 *
 * The service deliberately exposes stable language/currency codes and keeps
 * fallback behaviour in one place so views, JavaScript configuration,
 * documents, reports and notifications do not each invent their own rules.
 */
class ApplicationLocaleService
{
    /**
     * Browser-published first-paint language hint.
     *
     * Written by resources/js/core.js and read only when no organisation
     * is bound. It must stay out of Laravel's cookie encryption so the
     * browser can write a value the server can read (see bootstrap/app.php).
     */
    public const LANGUAGE_COOKIE = 'patrimoine_language';

    /**
     * Return the configured language code.
     *
     * Existing installations without persisted V1.0.2 values fall back to
     * the compatibility default.
     */
    public function language(): string
    {
        $supported =
            array_keys(
                config(
                    'patrimoine.languages',
                    []
                )
            );

        /*
         * V1.0.15: public screens (sign-in, sign-up, password ownership)
         * have no bound organisation, so the visitor's declared browser
         * language localises responses — validation messages, sign-in
         * errors — instead of the English platform default. Once an
         * organisation is bound, its own language remains authoritative.
         *
         * V1.0.44: the same declaration may now arrive as an ordinary
         * Accept-Language header, which is what a native client sends
         * and what a WebView cannot be relied on to carry in a cookie.
         *
         * The order matters. An explicit X-Patrimoine-Language is the
         * client saying what it is rendering in; Accept-Language is the
         * device saying what its owner prefers. The first is a statement
         * about the screen the reply will be shown on, so it wins.
         */
        $declared =
            $this->declaredLanguage($supported);

        if (
            $declared !== null
            && (bool) config(
                'patrimoine.client_language_overrides_organisation',
                false
            )
        ) {
            return $declared;
        }

        if (! \App\Support\OrganisationContext::bound()) {
            if ($declared !== null) {
                return $declared;
            }

            /*
             * Blade documents are requested by ordinary navigation and
             * carry no API token, so no organisation is ever bound while
             * rendering them and the English platform default would win.
             *
             * The browser publishes the language it has confirmed as a
             * plain cookie precisely so the server can render the right
             * language in the first byte rather than letting JavaScript
             * repaint the whole interface after boot. A bound
             * organisation still overrides this below.
             */
            $cookie =
                request()?->cookie(
                    self::LANGUAGE_COOKIE
                );

            if (
                is_string($cookie)
                && in_array(
                    $cookie,
                    $supported,
                    true
                )
            ) {
                return $cookie;
            }

            /*
             * V1.0.44: last, the device's own preference.
             *
             * It comes after the cookie deliberately. The cookie is the
             * language this visitor has already been reading Patrimoine
             * in; Accept-Language is whatever the handset was set up
             * with, and letting it win would repaint the sign-in screen
             * in the wrong language for anybody using an English phone
             * inside a French organisation.
             */
            $accepted =
                $this->acceptedLanguage($supported);

            if ($accepted !== null) {
                return $accepted;
            }
        }

        return $this->normalizedSetting(
            'language',
            $supported,
            (string) config(
                'patrimoine.defaults.language',
                'en'
            )
        );
    }

    /**
     * Return the configured currency code.
     */
    public function currency(): string
    {
        return $this->normalizedSetting(
            'currency',
            array_keys(
                config(
                    'patrimoine.currencies',
                    []
                )
            ),
            (string) config(
                'patrimoine.defaults.currency',
                'GHS'
            )
        );
    }

    /**
     * Return all registered language codes.
     *
     * @return list<string>
     */
    public function supportedLanguages(): array
    {
        return array_values(
            array_keys(
                config(
                    'patrimoine.languages',
                    []
                )
            )
        );
    }

    /**
     * Return all registered currency codes.
     *
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return array_values(
            array_keys(
                config(
                    'patrimoine.currencies',
                    []
                )
            )
        );
    }

    /**
     * Resolve the registered definition for a currency.
     *
     * @return array<string, mixed>
     */
    public function currencyDefinition(
        ?string $currency = null
    ): array {
        $currency ??= $this->currency();

        $definition =
            config(
                "patrimoine.currencies.{$currency}"
            );

        if (! is_array($definition)) {
            throw new InvalidArgumentException(
                "Unsupported Patrimoine currency [{$currency}]."
            );
        }

        return $definition;
    }

    /**
     * Apply the configured organisation language to Laravel.
     */
    public function applyLanguage(): void
    {
        App::setLocale(
            $this->language()
        );
    }

    /**
     * Return the presentation configuration required by browser JavaScript.
     *
     * This payload contains stable codes and formatting metadata only.
     * Translation strings will be introduced separately during UI
     * localisation activities.
     *
     * @return array<string, mixed>
     */
    public function browserConfiguration(): array
    {
        $language =
            $this->language();

        $currency =
            $this->currency();

        return [
            'language' => $language,

            'currency' => $currency,

            'locale' => (string) config(
                "patrimoine.languages.{$language}.locale",
                $language
            ),

            'browser_locale' => (string) config(
                "patrimoine.languages.{$language}.browser_locale",
                'en-GB'
            ),

            'currency_definition' => $this->currencyDefinition(
                $currency
            ),

            'supported_languages' => $this->supportedLanguages(),

            'supported_currencies' => $this->supportedCurrencies(),
        ];
    }

    /**
     * The language the client states it is rendering in.
     *
     * X-Patrimoine-Language is a statement about the screen the reply
     * will be shown on, not a preference — and getting it right is what
     * keeps an error message matchable back to its code, since codes are
     * recovered from the rendered sentence and each language has its own
     * map. It is the only client signal strong enough to be allowed, by
     * configuration, to overrule the organisation.
     *
     * @param  list<string>  $supported
     */
    private function declaredLanguage(array $supported): ?string
    {
        $declared = request()?->header('X-Patrimoine-Language');

        return is_string($declared)
            && in_array($declared, $supported, true)
                ? $declared
                : null;
    }

    /**
     * The language the device says its owner prefers.
     *
     * Every browser sends this whether it means anything or not, which
     * is why it ranks below both the explicit declaration and the cookie
     * the browser itself wrote. It matters for the client that has
     * neither: a native application on its very first call, before it
     * has been told anything.
     *
     * @param  list<string>  $supported
     */
    private function acceptedLanguage(array $supported): ?string
    {
        $request = request();

        if ($request === null) {
            return null;
        }

        $accept = $request->header('Accept-Language');

        if (! is_string($accept) || trim($accept) === '') {
            return null;
        }

        /*
         * Accept-Language is a weighted list: "fr-FR,fr;q=0.9,en;q=0.8".
         * Each entry is reduced to its primary subtag, because Patrimoine
         * registers languages, not regional variants, and they are tried
         * in the order the client ranked them.
         */
        $ranked = [];

        foreach (explode(',', $accept) as $entry) {
            $parts = explode(';q=', trim($entry), 2);

            $tag = mb_strtolower(trim($parts[0]));

            if ($tag === '' || $tag === '*') {
                continue;
            }

            $quality = isset($parts[1])
                ? (float) $parts[1]
                : 1.0;

            $primary = explode('-', $tag)[0];

            if (! in_array($primary, $supported, true)) {
                continue;
            }

            /*
             * A repeated language keeps the highest weight it was given.
             */
            $ranked[$primary] = max(
                $ranked[$primary] ?? 0.0,
                $quality
            );
        }

        if ($ranked === []) {
            return null;
        }

        arsort($ranked);

        return (string) array_key_first($ranked);
    }

    /**
     * Return a safe persisted setting or its compatibility default.
     *
     * @param  list<string>  $supportedValues
     */
    private function normalizedSetting(
        string $attribute,
        array $supportedValues,
        string $fallback
    ): string {
        /*
         * Locale resolution runs globally, including during login, initial
         * setup and other requests that may execute before Patrimoine's
         * database schema is available.
         *
         * In that state, presentation must safely use compatibility defaults
         * rather than prevent the application from booting.
         */
        if (! Schema::hasTable('application_settings')) {
            return $fallback;
        }

        /*
         * V1.0.10 multi-tenancy: presentation settings belong to one
         * organisation. Without a bound organisation context (public
         * pages, sign-in) there is no organisation whose preference
         * could legitimately apply, so the platform default is used
         * instead of leaking an arbitrary organisation's choice.
         */
        if (! \App\Support\OrganisationContext::bound()) {
            return $fallback;
        }

        $settings =
            ApplicationSetting::query()
                ->first();

        $value =
            trim(
                (string) (
                    $settings?->{$attribute}
                    ?? ''
                )
            );

        if (
            $value !== ''
            && in_array(
                $value,
                $supportedValues,
                true
            )
        ) {
            return $value;
        }

        return $fallback;
    }
}
