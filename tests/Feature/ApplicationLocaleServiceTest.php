<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Services\ApplicationLocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Verify the organisation-level language/currency foundation.
 */
class ApplicationLocaleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_installation_uses_compatibility_defaults(): void
    {
        $service =
            app(
                ApplicationLocaleService::class
            );

        $this->assertSame(
            'en',
            $service->language()
        );

        $this->assertSame(
            'GHS',
            $service->currency()
        );
    }

    public function test_language_and_currency_are_independent_settings(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $service =
            app(
                ApplicationLocaleService::class
            );

        $this->assertSame(
            'fr',
            $service->language()
        );

        $this->assertSame(
            'GHS',
            $service->currency()
        );
    }

    public function test_all_v1_0_2_combinations_are_supported(): void
    {
        $service =
            app(
                ApplicationLocaleService::class
            );

        $this->assertSame(
            [
                'en',
                'fr',
            ],
            $service->supportedLanguages()
        );

        $this->assertSame(
            [
                'GHS',
                'FCFA',
            ],
            $service->supportedCurrencies()
        );
    }

    public function test_configured_language_can_be_applied_to_laravel(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'FCFA',
        ]);

        $service =
            app(
                ApplicationLocaleService::class
            );

        $service->applyLanguage();

        $this->assertSame(
            'fr',
            App::currentLocale()
        );
    }

    public function test_currency_definition_is_resolved_from_central_registry(): void
    {
        $service =
            app(
                ApplicationLocaleService::class
            );

        $definition =
            $service->currencyDefinition(
                'FCFA'
            );

        $this->assertSame(
            ' ',
            $definition['group_separator']
        );

        $this->assertSame(
            'after',
            $definition['symbol_position']
        );
    }


    public function test_locale_resolution_is_safe_before_application_settings_table_exists(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists(
            'application_settings'
        );

        $service =
            app(
                ApplicationLocaleService::class
            );

        $this->assertSame(
            'en',
            $service->language()
        );

        $this->assertSame(
            'GHS',
            $service->currency()
        );

        $service->applyLanguage();

        $this->assertSame(
            'en',
            App::currentLocale()
        );
    }

}
