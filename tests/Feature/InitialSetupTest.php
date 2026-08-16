<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Protect Patrimoine's one-time initial installation workflow.
 *
 * V1.0.2 requirements:
 *
 * - a fresh installation starts in English;
 * - the first Administrator and Managing Organisation are created together;
 * - language and currency are selected during initial setup;
 * - language/currency belong to ApplicationSetting, not User;
 * - all four supported language/currency combinations are valid;
 * - subsequent login/presentation follows the saved organisation settings;
 * - setup cannot be used again after bootstrap.
 */
class InitialSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Rate limiting is an operational protection rather than business
         * behaviour under test here. Disabling it prevents several setup
         * scenarios in the same test run from sharing throttle state.
         */
        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    /**
     * A genuinely fresh Patrimoine installation requires setup.
     */
    public function test_fresh_installation_requires_initial_setup(): void
    {
        $this
            ->getJson('/api/setup/status')
            ->assertOk()
            ->assertExactJson([
                'setup_required' => true,
            ]);

        $this->assertDatabaseCount(
            'users',
            0
        );

        $this->assertDatabaseCount(
            'application_settings',
            0
        );
    }

    /**
     * The browser setup surface deliberately starts in English.
     */
    public function test_initial_setup_page_starts_in_english(): void
    {
        $response =
            $this->get('/setup');

        $response
            ->assertOk()
            ->assertSee(
                'Initial Setup'
            )
            ->assertSee(
                'Administrator'
            )
            ->assertSee(
                'Managing Organisation'
            )
            ->assertSee(
                'Application Settings'
            )
            ->assertSee(
                'English'
            )
            ->assertSee(
                'Français'
            )
            ->assertSee(
                'GHS'
            )
            ->assertSee(
                'FCFA'
            );
    }

    /**
     * Language and currency are independent during first installation.
     */
    public function test_setup_accepts_all_supported_language_currency_combinations(): void
    {
        foreach (
            self::supportedPresentationCombinations()
            as $combination
        ) {
            [$language, $currency] =
                $combination;

            /*
             * RefreshDatabase wraps each test method rather than each loop
             * iteration, so explicitly clear the bootstrap records before
             * exercising the next independent combination.
             */
            \Illuminate\Support\Facades\DB::table(
                'personal_access_tokens'
            )->delete();

            ApplicationSetting::query()
                ->delete();

            \App\Models\PartyRole::query()
                ->delete();

            Party::query()
                ->delete();

            User::query()
                ->delete();

            $response =
                $this->postJson(
                    '/api/setup',
                    $this->validPayload(
                        language: $language,
                        currency: $currency
                    )
                );

            $response
                ->assertCreated()
                ->assertJsonPath(
                    'language',
                    $language
                )
                ->assertJsonPath(
                    'currency',
                    $currency
                );

            $settings =
                ApplicationSetting::query()
                    ->firstOrFail();

            $this->assertSame(
                $language,
                $settings->language
            );

            $this->assertSame(
                $currency,
                $settings->currency
            );

            /*
             * Presentation choices belong to ApplicationSetting rather than
             * the administrator account.
             */
            $user =
                User::query()
                    ->firstOrFail();

            $this->assertArrayNotHasKey(
                'language',
                $user->getAttributes()
            );

            $this->assertArrayNotHasKey(
                'currency',
                $user->getAttributes()
            );
        }
    }

    /**
     * A complete French/FCFA setup creates all required bootstrap records.
     */
    public function test_initial_setup_creates_administrator_and_managing_organisation(): void
    {
        $this
            ->postJson(
                '/api/setup',
                $this->validPayload(
                    language: 'fr',
                    currency: 'FCFA'
                )
            )
            ->assertCreated();

        $this->assertDatabaseCount(
            'users',
            1
        );

        $this->assertDatabaseCount(
            'application_settings',
            1
        );

        $this->assertDatabaseHas(
            'users',
            [
                'name' =>
                    'Initial Administrator',

                'email' =>
                    'admin@example.test',

                'role' =>
                    UserRole::Administrator->value,
            ]
        );

        $this->assertDatabaseHas(
            'parties',
            [
                'type' =>
                    'organisation',

                'legal_name' =>
                    'Example Property Management Ltd',

                'address' =>
                    '1 Example Street, Accra',

                'contact_person_name' =>
                    'Initial Administrator',

                'contact_person_phone' =>
                    '0200000000',

                'contact_person_email' =>
                    'admin@example.test',
            ]
        );

        $organisation =
            Party::query()
                ->where(
                    'legal_name',
                    'Example Property Management Ltd'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'party_roles',
            [
                'party_id' =>
                    $organisation->id,

                'role' =>
                    'managing_organisation',
            ]
        );

        $settings =
            ApplicationSetting::query()
                ->firstOrFail();

        $this->assertSame(
            $organisation->id,
            $settings
                ->managing_organisation_party_id
        );

        $this->assertSame(
            'fr',
            $settings->language
        );

        $this->assertSame(
            'FCFA',
            $settings->currency
        );

        $this->assertSame(
            '18.00',
            $settings->default_vat_rate
        );
    }

    /**
     * The initial administrator password must be stored securely and work
     * through the normal authentication endpoint.
     */
    public function test_initial_administrator_can_authenticate_after_setup(): void
    {
        $plainPassword =
            'SecureSetup#123';

        $this
            ->postJson(
                '/api/setup',
                $this->validPayload(
                    language: 'fr',
                    currency: 'FCFA',
                    password: $plainPassword
                )
            )
            ->assertCreated();

        $user =
            User::query()
                ->firstOrFail();

        $this->assertNotSame(
            $plainPassword,
            $user->password
        );

        $this->assertTrue(
            Hash::check(
                $plainPassword,
                $user->password
            )
        );

        $this
            ->postJson(
                '/api/auth/login',
                [
                    'email' =>
                        'admin@example.test',

                    'password' =>
                        $plainPassword,

                    'device_name' =>
                        'initial-setup-test',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                'admin@example.test'
            )
            ->assertJsonPath(
                'user.role',
                UserRole::Administrator->value
            );
    }

    /**
     * Once initial configuration is stored, the public presentation endpoint
     * exposes it so the next login screen can adopt the selected language.
     */
    public function test_presentation_follows_initial_setup_selection(): void
    {
        $this
            ->postJson(
                '/api/setup',
                $this->validPayload(
                    language: 'fr',
                    currency: 'FCFA'
                )
            )
            ->assertCreated();

        $this
            ->getJson('/api/presentation-config')
            ->assertOk()
            ->assertJsonPath(
                'language',
                'fr'
            )
            ->assertJsonPath(
                'currency',
                'FCFA'
            );
    }

    /**
     * Initial setup is permanently disabled after successful bootstrap.
     */
    public function test_setup_cannot_be_completed_twice(): void
    {
        $this
            ->postJson(
                '/api/setup',
                $this->validPayload()
            )
            ->assertCreated();

        $this
            ->getJson('/api/setup/status')
            ->assertOk()
            ->assertExactJson([
                'setup_required' => false,
            ]);

        $this
            ->postJson(
                '/api/setup',
                $this->validPayload(
                    administratorEmail:
                        'second@example.test'
                )
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Initial setup has already been completed.'
            );

        $this->assertDatabaseCount(
            'users',
            1
        );

        $this->assertDatabaseCount(
            'application_settings',
            1
        );
    }

    /**
     * An administrator created through the existing secure CLI bootstrap
     * disables the browser bootstrap endpoint.
     */
    public function test_existing_user_disables_initial_setup(): void
    {
        User::factory()->create();

        $this
            ->getJson('/api/setup/status')
            ->assertOk()
            ->assertExactJson([
                'setup_required' => false,
            ]);

        $this
            ->postJson(
                '/api/setup',
                $this->validPayload()
            )
            ->assertStatus(409);

        $this->assertDatabaseCount(
            'users',
            1
        );
    }

    /**
     * A configured Managing Organisation also disables bootstrap even if an
     * unusual partial installation contains no User record.
     */
    public function test_existing_managing_organisation_disables_initial_setup(): void
    {
        $organisation =
            Party::create([
                'type' =>
                    'organisation',

                'legal_name' =>
                    'Existing Organisation',

                'address' =>
                    'Existing Address',

                'contact_person_name' =>
                    'Existing Contact',

                'contact_person_phone' =>
                    '0200000001',

                'contact_person_email' =>
                    'existing@example.test',
            ]);

        $organisation
            ->roles()
            ->create([
                'role' =>
                    'managing_organisation',
            ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' =>
                $organisation->id,

            'default_vat_rate' =>
                18,

            'language' =>
                'en',

            'currency' =>
                'GHS',
        ]);

        $this
            ->getJson('/api/setup/status')
            ->assertOk()
            ->assertExactJson([
                'setup_required' => false,
            ]);

        $this
            ->postJson(
                '/api/setup',
                $this->validPayload()
            )
            ->assertStatus(409);

        $this->assertDatabaseCount(
            'users',
            0
        );
    }

    /**
     * Unsupported presentation values must never enter persisted settings.
     */
    public function test_initial_setup_rejects_unsupported_language_and_currency(): void
    {
        $payload =
            $this->validPayload();

        $payload['language'] =
            'xx';

        $payload['currency'] =
            'XYZ';

        $this
            ->postJson(
                '/api/setup',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'language',
                'currency',
            ]);

        $this->assertDatabaseCount(
            'users',
            0
        );

        $this->assertDatabaseCount(
            'application_settings',
            0
        );
    }

    /**
     * Setup uses a stronger bootstrap password policy than normal login.
     */
    public function test_initial_setup_rejects_weak_administrator_password(): void
    {
        $this
            ->postJson(
                '/api/setup',
                $this->validPayload(
                    password:
                        'weakpassword'
                )
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'administrator_password',
            ]);

        $this->assertDatabaseCount(
            'users',
            0
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function supportedPresentationCombinations(): array
    {
        return [
            'English + GHS' => [
                'en',
                'GHS',
            ],

            'English + FCFA' => [
                'en',
                'FCFA',
            ],

            'French + GHS' => [
                'fr',
                'GHS',
            ],

            'French + FCFA' => [
                'fr',
                'FCFA',
            ],
        ];
    }

    /**
     * Build a complete valid setup payload.
     *
     * @return array<string, mixed>
     */
    private function validPayload(
        string $language = 'en',
        string $currency = 'GHS',
        string $administratorEmail = 'admin@example.test',
        string $password = 'SecureSetup#123'
    ): array {
        return [
            'administrator_name' =>
                'Initial Administrator',

            'administrator_email' =>
                $administratorEmail,

            'administrator_password' =>
                $password,

            'administrator_password_confirmation' =>
                $password,

            'legal_name' =>
                'Example Property Management Ltd',

            'address' =>
                '1 Example Street, Accra',

            'phone' =>
                '0302000000',

            'email' =>
                'office@example.test',

            'contact_person_name' =>
                'Initial Administrator',

            'contact_person_phone' =>
                '0200000000',

            'contact_person_email' =>
                'admin@example.test',

            'default_vat_rate' =>
                18,

            'language' =>
                $language,

            'currency' =>
                $currency,
        ];
    }
}
