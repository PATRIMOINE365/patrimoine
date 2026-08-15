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
     * Return the configured language code.
     *
     * Existing installations without persisted V1.0.2 values fall back to
     * the compatibility default.
     */
    public function language(): string
    {
        return $this->normalizedSetting(
            'language',
            array_keys(
                config(
                    'patrimoine.languages',
                    []
                )
            ),
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
            'language' =>
                $language,

            'currency' =>
                $currency,

            'locale' =>
                (string) config(
                    "patrimoine.languages.{$language}.locale",
                    $language
                ),

            'currency_definition' =>
                $this->currencyDefinition(
                    $currency
                ),

            'supported_languages' =>
                $this->supportedLanguages(),

            'supported_currencies' =>
                $this->supportedCurrencies(),
        ];
    }

    /**
     * Return a safe persisted setting or its compatibility default.
     *
     * @param list<string> $supportedValues
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
