<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Party;
use App\Models\User;
use App\Support\OrganisationContext;
use Laravel\Sanctum\Sanctum;

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

    public function test_presentation_config_exposes_safe_default_vat_rate(): void
    {
        $response =
            $this->getJson(
                '/api/presentation-config'
            )
                ->assertOk();

        $this->assertSame(
            18.0,
            (float) $response->json(
                'default_vat_rate'
            )
        );
    }

    /**
     * V1.0.50: before sign-in there is no organisation, so nothing of
     * any organisation is answered. The first organisation's legal
     * name, VAT default and switches used to be handed to every
     * anonymous caller because the scope adds no filter while unbound.
     */
    public function test_anonymous_presentation_config_reveals_no_organisation(): void
    {
        $organisation = Party::create([
            'type' => 'organisation',
            'name' => 'Home Advantage Ltd',
            'legal_name' => 'Home Advantage Ltd',
        ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' => $organisation->id,
            'language' => 'fr',
            'currency' => 'FCFA',
            'default_vat_rate' => 12.5,
            'party_emails_enabled' => false,
            'data_tools_enabled' => true,
            'sort_parties_by_surname' => true,
        ]);

        OrganisationContext::forget();

        $anonymous = $this
            ->getJson('/api/presentation-config')
            ->assertOk()
            ->assertJsonPath('organisation_name', 'Patrimoine')
            ->assertJsonPath('language', 'en')
            ->assertJsonPath('party_emails_enabled', true)
            ->assertJsonPath('data_tools_enabled', false)
            ->assertJsonPath('sort_parties_by_surname', false);

        $this->assertSame(
            18.0,
            (float) $anonymous->json('default_vat_rate')
        );

        /*
         * Signed in, the same endpoint answers with the caller's own
         * organisation, exactly as before.
         */
        Sanctum::actingAs(User::factory()->create());

        $signedIn = $this
            ->getJson('/api/presentation-config')
            ->assertOk()
            ->assertJsonPath('organisation_name', 'Home Advantage Ltd')
            ->assertJsonPath('language', 'fr')
            ->assertJsonPath('party_emails_enabled', false)
            ->assertJsonPath('data_tools_enabled', true)
            ->assertJsonPath('sort_parties_by_surname', true);

        $this->assertSame(
            12.5,
            (float) $signedIn->json('default_vat_rate')
        );
    }
}
