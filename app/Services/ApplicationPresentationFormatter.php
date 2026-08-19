<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Centralize user-facing monetary and date presentation for Patrimoine.
 *
 * Monetary values remain whole integers. Formatting never converts,
 * rescales or otherwise changes the underlying monetary value.
 *
 * Currency presentation is intentionally independent from language:
 *
 * - GHS:  GH₵ 1,255,000
 * - FCFA: 1 255 000 FCFA
 *
 * Date presentation follows the configured application language.
 */
class ApplicationPresentationFormatter
{
    public function __construct(
        private readonly ApplicationLocaleService $localeService
    ) {}

    /**
     * Format a whole-number monetary value using the active organisation
     * currency unless an explicit supported currency is supplied.
     */
    public function money(
        int|float|string|null $value,
        ?string $currency = null
    ): string {
        $amount = $this->integerValue($value);

        $currency = strtoupper(
            $currency
            ?? $this->localeService->currency()
        );

        $definition =
            $this->localeService
                ->currencyDefinition(
                    $currency
                );

        $separator =
            (string) (
                $definition[
                    'group_separator'
                ]
                ?? ','
            );

        $symbol =
            (string) (
                $definition['symbol']
                ?? $currency
            );

        $position =
            (string) (
                $definition[
                    'symbol_position'
                ]
                ?? 'before'
            );

        $absolute =
            number_format(
                abs($amount),
                0,
                '.',
                $separator
            );

        if ($position === 'after') {
            return $amount < 0
                ? "- {$absolute} {$symbol}"
                : "{$absolute} {$symbol}";
        }

        return $amount < 0
            ? "{$symbol} - {$absolute}"
            : "{$symbol} {$absolute}";
    }

    /**
     * Format a date according to the active organisation language.
     *
     * Date-only values are treated as calendar dates rather than instants,
     * avoiding accidental timezone shifts in presentation.
     */
    public function date(
        DateTimeInterface|string|null $value,
        ?string $language = null
    ): string {
        if ($value === null || $value === '') {
            return '';
        }

        $language =
            strtolower(
                $language
                ?? $this->localeService->language()
            );

        $date =
            $value instanceof DateTimeInterface
                ? $value
                : $this->parseDate($value);

        if (! $date) {
            return (string) $value;
        }

        /*
         * Patrimoine business-date presentation standard:
         *
         * French:  DD-MM-YYYY
         * English: DD/MM/YYYY
         *
         * These are presentation values only. Stored/API dates remain
         * ISO YYYY-MM-DD.
         */
        return $date->format(
            $language === 'fr'
                ? 'd-m-Y'
                : 'd/m/Y'
        );
    }

    private function integerValue(
        int|float|string|null $value
    ): int {
        if (
            $value === null
            || $value === ''
            || ! is_numeric($value)
        ) {
            return 0;
        }

        return (int) $value;
    }

    private function parseDate(
        string $value
    ): ?DateTimeInterface {
        $dateValue =
            substr(
                $value,
                0,
                10
            );

        try {
            return CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $dateValue,
                config('app.timezone')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
