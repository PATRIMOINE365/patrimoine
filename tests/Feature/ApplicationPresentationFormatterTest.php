<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Services\ApplicationPresentationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify canonical Patrimoine money and date presentation.
 *
 * Formatting must not modify stored values or perform currency conversion.
 */
class ApplicationPresentationFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_ghs_money_uses_canonical_format(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            'GH₵ 1,255,000',
            $formatter->money(
                1255000,
                'GHS'
            )
        );

        $this->assertSame(
            'GH₵ - 5,000',
            $formatter->money(
                -5000,
                'GHS'
            )
        );

        $this->assertSame(
            'GH₵ 0',
            $formatter->money(
                0,
                'GHS'
            )
        );
    }

    public function test_fcfa_money_uses_canonical_format(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            '1 255 000 FCFA',
            $formatter->money(
                1255000,
                'FCFA'
            )
        );

        $this->assertSame(
            '- 5 000 FCFA',
            $formatter->money(
                -5000,
                'FCFA'
            )
        );

        $this->assertSame(
            '0 FCFA',
            $formatter->money(
                0,
                'FCFA'
            )
        );
    }

    public function test_currency_formatting_is_independent_from_language(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            'GH₵ 1,255,000',
            $formatter->money(
                1255000
            )
        );

        ApplicationSetting::query()
            ->firstOrFail()
            ->update([
                'language' => 'en',
                'currency' => 'FCFA',
            ]);

        $this->assertSame(
            '1 255 000 FCFA',
            $formatter->money(
                1255000
            )
        );
    }

    public function test_english_date_uses_registered_date_locale(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            '15/08/2026',
            $formatter->date(
                '2026-08-15',
                'en'
            )
        );
    }

    public function test_french_date_uses_registered_date_locale(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            '15-08-2026',
            $formatter->date(
                '2026-08-15',
                'fr'
            )
        );
    }

    public function test_invalid_money_input_displays_as_zero_without_conversion(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            'GH₵ 0',
            $formatter->money(
                null,
                'GHS'
            )
        );

        $this->assertSame(
            '0 FCFA',
            $formatter->money(
                'not-a-number',
                'FCFA'
            )
        );
    }

    public function test_invalid_date_is_preserved_for_safe_display(): void
    {
        $formatter =
            app(
                ApplicationPresentationFormatter::class
            );

        $this->assertSame(
            'not-a-date',
            $formatter->date(
                'not-a-date',
                'en'
            )
        );
    }
}
