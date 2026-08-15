<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify the public browser presentation configuration endpoint.
 */
class ApplicationPresentationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_presentation_config_is_public_and_uses_defaults(): void
    {
        $this
            ->getJson(
                '/api/presentation-config'
            )
            ->assertOk()
            ->assertJsonPath(
                'language',
                'en'
            )
            ->assertJsonPath(
                'currency',
                'GHS'
            )
            ->assertJsonPath(
                'locale',
                'en'
            )
            ->assertJsonPath(
                'browser_locale',
                'en-GB'
            );
    }

    public function test_presentation_config_exposes_independent_settings(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $this
            ->getJson(
                '/api/presentation-config'
            )
            ->assertOk()
            ->assertJsonPath(
                'language',
                'fr'
            )
            ->assertJsonPath(
                'currency',
                'GHS'
            )
            ->assertJsonPath(
                'currency_definition.code',
                'GHS'
            )
            ->assertJsonPath(
                'browser_locale',
                'fr-FR'
            );
    }

    public function test_presentation_config_exposes_registered_supported_values(): void
    {
        $response =
            $this
                ->getJson(
                    '/api/presentation-config'
                )
                ->assertOk();

        $response->assertJson([
            'supported_languages' => [
                'en',
                'fr',
            ],

            'supported_currencies' => [
                'GHS',
                'FCFA',
            ],
        ]);
    }
}
