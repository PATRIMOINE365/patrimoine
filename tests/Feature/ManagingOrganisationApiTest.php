<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verify management of Patrimoine's singleton managing organisation.
 */
class ManagingOrganisationApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser('administrator');
    }

    /**
     * A fresh installation has no managing organisation yet.
     */
    public function test_managing_organisation_returns_not_found_before_configuration(): void
    {
        $this
            ->getJson('/api/managing-organisation')
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Managing organisation has not been configured.'
            );
    }

    /**
     * The managing organisation can be configured for the first time.
     */
    public function test_managing_organisation_can_be_configured(): void
    {
        $response = $this->putJson(
            '/api/managing-organisation',
            $this->validPayload()
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'type',
                'organisation'
            )
            ->assertJsonPath(
                'legal_name',
                'Patrimoine Management Limited'
            );

        $party = Party::query()->firstOrFail();

        $this->assertDatabaseHas(
            'party_roles',
            [
                'party_id' => $party->id,
                'role' => 'managing_organisation',
            ]
        );

        $this->assertDatabaseHas(
            'application_settings',
            [
                'managing_organisation_party_id' =>
                    $party->id,
            ]
        );
    }

    /**
     * Updating configuration reuses the existing Party.
     */
    public function test_managing_organisation_update_reuses_existing_party(): void
    {
        $this
            ->putJson(
                '/api/managing-organisation',
                $this->validPayload()
            )
            ->assertOk();

        $originalPartyId = ApplicationSetting::query()
            ->firstOrFail()
            ->managing_organisation_party_id;

        $payload = $this->validPayload();
        $payload['legal_name'] =
            'Updated Patrimoine Management Limited';

        $this
            ->putJson(
                '/api/managing-organisation',
                $payload
            )
            ->assertOk()
            ->assertJsonPath(
                'legal_name',
                'Updated Patrimoine Management Limited'
            );

        $settings = ApplicationSetting::query()
            ->firstOrFail();

        $this->assertSame(
            $originalPartyId,
            $settings->managing_organisation_party_id
        );

        $this->assertSame(
            1,
            Party::query()->count()
        );
    }

    /**
     * The configured organisation can be retrieved.
     */
    public function test_managing_organisation_can_be_retrieved(): void
    {
        $this
            ->putJson(
                '/api/managing-organisation',
                $this->validPayload()
            )
            ->assertOk();

        $this
            ->getJson('/api/managing-organisation')
            ->assertOk()
            ->assertJsonPath(
                'legal_name',
                'Patrimoine Management Limited'
            )
            ->assertJsonPath(
                'roles.0.role',
                'managing_organisation'
            );
    }

    /**
     * Required organisation identity fields are validated.
     */
    public function test_managing_organisation_requires_identity_fields(): void
    {
        $this
            ->putJson(
                '/api/managing-organisation',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'legal_name',
                'address',
                'contact_person_name',
                'contact_person_phone',
                'contact_person_email',
            ]);
    }

    /**
     * Managing organisation endpoints remain Property Manager protected.
     */
    public function test_managing_organisation_requires_authentication(): void
    {
        auth()->forgetGuards();

        $this
            ->getJson('/api/managing-organisation')
            ->assertUnauthorized();
    }


    /**
     * The configured managing organisation cannot lose its protected role
     * through the generic Party API.
     */
    public function test_configured_managing_organisation_role_cannot_be_removed(): void
    {
        $this
            ->putJson(
                '/api/managing-organisation',
                $this->validPayload()
            )
            ->assertOk();

        $party = Party::query()->firstOrFail();

        $this
            ->putJson(
                "/api/parties/{$party->id}",
                [
                    'type' => 'organisation',
                    'legal_name' => $party->legal_name,
                    'address' => $party->address,
                    'contact_person_name' =>
                        $party->contact_person_name,
                    'contact_person_phone' =>
                        $party->contact_person_phone,
                    'contact_person_email' =>
                        $party->contact_person_email,
                    'roles' => [],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'roles',
            ]);

        $this->assertDatabaseHas(
            'party_roles',
            [
                'party_id' => $party->id,
                'role' => 'managing_organisation',
            ]
        );
    }

    /**
     * The configured managing organisation cannot be deleted through the
     * generic Party API.
     */
    public function test_configured_managing_organisation_cannot_be_deleted(): void
    {
        $this
            ->putJson(
                '/api/managing-organisation',
                $this->validPayload()
            )
            ->assertOk();

        $party = Party::query()->firstOrFail();

        $this
            ->deleteJson(
                "/api/parties/{$party->id}"
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The configured Managing Organisation cannot be deleted. Change the Managing Organisation configuration instead.'
            );

        $this->assertDatabaseHas(
            'parties',
            [
                'id' => $party->id,
            ]
        );

        $this->assertDatabaseHas(
            'application_settings',
            [
                'managing_organisation_party_id' =>
                    $party->id,
            ]
        );
    }




/**
 * The Managing Organisation API exposes the application-wide
 * default VAT rate.
 */
public function test_managing_organisation_exposes_default_vat_rate(): void
{
    $payload =
        $this->validPayload();

    $payload['default_vat_rate'] =
        15.00;

    $this
        ->putJson(
            '/api/managing-organisation',
            $payload
        )
        ->assertOk()
        ->assertJsonPath(
            'default_vat_rate',
            '15.00'
        );

    $this
        ->getJson(
            '/api/managing-organisation'
        )
        ->assertOk()
        ->assertJsonPath(
            'default_vat_rate',
            '15.00'
        );

    $this->assertDatabaseHas(
        'application_settings',
        [
            'default_vat_rate' =>
                15.00,
        ]
    );
}

/**
 * Default VAT must remain a valid percentage.
 */
public function test_managing_organisation_rejects_invalid_default_vat_rate(): void
{
    $payload =
        $this->validPayload();

    $payload['default_vat_rate'] =
        150;

    $this
        ->putJson(
            '/api/managing-organisation',
            $payload
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'default_vat_rate',
        ]);
}

    /**
     * Return a complete valid managing-organisation payload.
     *
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'legal_name' =>
                'Patrimoine Management Limited',

            'address' =>
                'Accra, Ghana',

            'phone' =>
                '0302000000',

            'email' =>
                'info@patrimoine.example',

            'contact_person_name' =>
                'Property Manager',

            'contact_person_phone' =>
                '0200000000',

            'contact_person_email' =>
                'manager@patrimoine.example',

            'registration_number' =>
                'CS000000000',

            'vat_tin' =>
                'C0000000000',

            'bank_name' =>
                'Example Bank',

            'bank_account_name' =>
                'Patrimoine Management Limited',

            'bank_account_number' =>
                '0000000000',

            'bank_branch' =>
                'Accra',

            'default_vat_rate' =>
                18.00,

            'notes' =>
                'Primary Patrimoine managing organisation.',
        ];
    }


    public function test_missing_language_and_currency_use_compatibility_defaults(): void
    {
        $response =
            $this->putJson(
                '/api/managing-organisation',
                $this->validPayload()
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'language',
                'en'
            )
            ->assertJsonPath(
                'currency',
                'GHS'
            );

        $settings =
            ApplicationSetting::query()
                ->firstOrFail();

        $this->assertSame(
            'en',
            $settings->language
        );

        $this->assertSame(
            'GHS',
            $settings->currency
        );
    }

    public function test_managing_organisation_rejects_unsupported_language_and_currency(): void
    {
        $payload =
            array_merge(
                $this->validPayload(),
                [
                    'language' => 'xx',
                    'currency' => 'XYZ',
                ]
            );

        $this
            ->putJson(
                '/api/managing-organisation',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'language',
                'currency',
            ]);
    }



    public function test_language_and_currency_can_be_updated_independently(): void
    {
        $payload =
            array_merge(
                $this->validPayload(),
                [
                    'language' => 'fr',
                    'currency' => 'GHS',
                ]
            );

        $this
            ->putJson(
                '/api/managing-organisation',
                $payload
            )
            ->assertOk()
            ->assertJsonPath(
                'language',
                'fr'
            )
            ->assertJsonPath(
                'currency',
                'GHS'
            );

        $payload['language'] = 'en';
        $payload['currency'] = 'FCFA';

        $this
            ->putJson(
                '/api/managing-organisation',
                $payload
            )
            ->assertOk()
            ->assertJsonPath(
                'language',
                'en'
            )
            ->assertJsonPath(
                'currency',
                'FCFA'
            );

        $settings =
            ApplicationSetting::query()
                ->firstOrFail();

        $this->assertSame(
            'en',
            $settings->language
        );

        $this->assertSame(
            'FCFA',
            $settings->currency
        );
    }

}
