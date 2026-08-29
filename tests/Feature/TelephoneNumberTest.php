<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.30 telephone numbers: a country, a number, and one stored form.
 *
 * What the column holds is E.164 — the only shape an SMS or WhatsApp
 * gateway will dial — and the country beside it is what lets the right
 * flag come back, which a shared calling code like +1 could never say.
 */
class TelephoneNumberTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /*
    |--------------------------------------------------------------------------
    | Composing and reading
    |--------------------------------------------------------------------------
    */

    public function test_a_country_and_a_number_become_one_dialable_number(): void
    {
        $this->assertSame(
            '+233244347118',
            PhoneNumber::compose('GH', '024 434 7118')
        );

        /*
         * The trunk zero is how a number is dialled from inside the
         * country and is never part of the international form.
         */
        $this->assertSame(
            '+233244347118',
            PhoneNumber::compose('GH', '0244347118')
        );

        $this->assertSame(
            '+22890123456',
            PhoneNumber::compose('TG', '(90) 12-34-56')
        );

        $this->assertNull(
            PhoneNumber::compose('GH', '')
        );

        $this->assertNull(
            PhoneNumber::compose(null, '0244347118')
        );

        $this->assertNull(
            PhoneNumber::compose('ZZ', '0244347118')
        );
    }

    public function test_italy_keeps_the_zero_that_everywhere_else_drops(): void
    {
        /*
         * A Rome landline really is +39 06 …; the zero is part of the
         * number rather than a way of reaching it from inside Italy.
         */
        $this->assertSame(
            '+390612345678',
            PhoneNumber::compose('IT', '06 1234 5678')
        );

        $this->assertTrue(
            PhoneNumber::keepsTrunkZero('IT')
        );

        $this->assertFalse(
            PhoneNumber::keepsTrunkZero('GH')
        );

        /*
         * The browser has to agree, or a number typed in one place would
         * not match the same number typed in the other.
         */
        $javascript = file_get_contents(
            resource_path('js/countries.js')
        );

        foreach (config('countries.keeps_trunk_zero') as $iso) {
            $this->assertStringContainsString(
                "    '{$iso}',",
                $javascript
            );
        }
    }

    public function test_a_stored_number_gives_back_its_country(): void
    {
        $this->assertSame(
            'GH',
            PhoneNumber::countryFor('+233244347118')
        );

        $this->assertSame(
            'TG',
            PhoneNumber::countryFor('+22890123456')
        );

        /*
         * The longest code wins, so +1 is only reached once the four-digit
         * candidates have been ruled out.
         */
        $this->assertSame(
            'FR',
            PhoneNumber::countryFor('+33612345678')
        );

        $this->assertNull(
            PhoneNumber::countryFor('0244347118')
        );

        $this->assertNull(
            PhoneNumber::countryFor(null)
        );
    }

    public function test_a_shared_calling_code_answers_with_its_configured_country(): void
    {
        /*
         * +1 covers twenty-odd countries and +7 covers two. Reading one
         * backwards can only ever give the commonest answer, which is
         * exactly why the chosen country is stored rather than inferred.
         */
        $this->assertSame(
            'US',
            PhoneNumber::countryFor('+15551234567')
        );

        $this->assertSame(
            'RU',
            PhoneNumber::countryFor('+79161234567')
        );

        $this->assertSame(
            'GB',
            PhoneNumber::countryFor('+447700900123')
        );
    }

    public function test_only_a_number_that_could_be_dialled_is_e164(): void
    {
        $this->assertTrue(
            PhoneNumber::isE164('+233244347118')
        );

        $this->assertFalse(
            PhoneNumber::isE164('0244347118'),
            'A national number is not dialable from anywhere else.'
        );

        $this->assertFalse(
            PhoneNumber::isE164('+233 244 347 118'),
            'E.164 carries no spacing.'
        );

        $this->assertFalse(
            PhoneNumber::isE164('+99912345678'),
            '+999 belongs to no country.'
        );

        $this->assertFalse(
            PhoneNumber::isE164('+2332'),
            'Too few digits to reach anybody.'
        );

        $this->assertFalse(
            PhoneNumber::isE164('+2332443471180000'),
            'E.164 stops at fifteen digits.'
        );
    }

    public function test_a_number_is_shown_with_its_calling_code_apart(): void
    {
        $this->assertSame(
            '+233 244347118',
            PhoneNumber::display('+233244347118', 'GH')
        );

        /*
         * The country column is a convenience, not a requirement: the
         * calling code is readable from the number itself.
         */
        $this->assertSame(
            '+233 244347118',
            PhoneNumber::display('+233244347118')
        );

        /*
         * A number recorded before V1.0.30 is free text and is shown
         * exactly as somebody typed it.
         */
        $this->assertSame(
            '024 434 7118',
            PhoneNumber::display('024 434 7118')
        );

        $this->assertNull(
            PhoneNumber::display(null)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The models
    |--------------------------------------------------------------------------
    */

    public function test_a_record_reads_its_own_numbers_back_for_a_person(): void
    {
        $party = Party::create([
            'type' => 'organisation',
            'name' => 'Kality Limited',
            'legal_name' => 'Kality Limited',
            'phone' => '+233244347118',
            'phone_country' => 'GH',
            'alternate_phone' => '+22890123456',
            'alternate_phone_country' => 'TG',
            'contact_person_name' => 'Ama Mensah',
            'contact_person_phone' => '+233200000200',
            'contact_person_phone_country' => 'GH',
            'contact_person_email' => 'ama@example.test',
        ]);

        $this->assertSame(
            '+233 244347118',
            $party->phone_display
        );

        $this->assertSame(
            '+228 90123456',
            $party->alternate_phone_display
        );

        $this->assertSame(
            '+233 200000200',
            $party->contact_person_phone_display
        );

        /*
         * The raw column is untouched: it is what gets dialled.
         */
        $this->assertSame(
            '+233244347118',
            $party->phone
        );
    }

    public function test_a_user_reads_its_number_back_too(): void
    {
        $user = User::query()->firstOrFail();

        $user->forceFill([
            'phone' => '+233244347118',
            'phone_country' => 'GH',
        ])->save();

        $this->assertSame(
            '+233 244347118',
            $user->refresh()->phone_display
        );
    }

    /*
    |--------------------------------------------------------------------------
    | What the API accepts
    |--------------------------------------------------------------------------
    */

    public function test_a_number_without_a_country_is_refused(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '0244347118',
                'email' => 'ama@example.test',
                'roles' => ['tenant'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_country'])
            ->assertJsonPath('code', 'PM-2072');
    }

    public function test_a_number_that_could_not_be_dialled_is_refused(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '+2332',
                'phone_country' => 'GH',
                'email' => 'ama@example.test',
                'roles' => ['tenant'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonPath('code', 'PM-2073');
    }

    public function test_a_number_belonging_to_another_country_is_refused(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '+22890123456',
                'phone_country' => 'GH',
                'email' => 'ama@example.test',
                'roles' => ['tenant'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_country'])
            ->assertJsonPath('code', 'PM-2073');
    }

    public function test_an_unknown_country_is_refused(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '+233244347118',
                'phone_country' => 'ZZ',
                'email' => 'ama@example.test',
                'roles' => ['tenant'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_country']);
    }

    public function test_a_country_and_a_number_are_stored_together(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '+233244347118',
                'phone_country' => 'GH',
                'email' => 'ama@example.test',
                'roles' => ['tenant'],
            ])
            ->assertCreated()
            ->assertJsonPath('phone', '+233244347118')
            ->assertJsonPath('phone_country', 'GH');

        $party = Party::query()->firstOrFail();

        $this->assertSame('+233244347118', $party->phone);
        $this->assertSame('GH', $party->phone_country);
    }

    public function test_a_record_may_still_carry_no_number_at_all(): void
    {
        $this
            ->postJson('/api/parties', [
                'type' => 'organisation',
                'legal_name' => 'Kality Limited',
                'contact_person_name' => 'Ama Mensah',
                'contact_person_phone' => '+233244347118',
                'contact_person_phone_country' => 'GH',
                'contact_person_email' => 'ama@example.test',
                'roles' => ['owner'],
            ])
            ->assertCreated()
            ->assertJsonPath('phone', null)
            ->assertJsonPath('alternate_phone', null);
    }

    /*
    |--------------------------------------------------------------------------
    | The country list
    |--------------------------------------------------------------------------
    */

    public function test_every_country_is_named_in_both_languages(): void
    {
        $codes = config('countries.dialling_codes');

        $this->assertGreaterThan(
            200,
            count($codes),
            'The picker offers every country, not a shortlist.'
        );

        foreach (['en', 'fr'] as $locale) {
            $names = trans('countries', [], $locale);

            $this->assertSame(
                array_keys($codes),
                array_keys($names),
                "The {$locale} names must cover exactly the countries we can dial."
            );

            foreach ($names as $iso => $name) {
                $this->assertNotSame(
                    $iso,
                    $name,
                    "{$iso} would print its own code instead of a name."
                );
            }
        }
    }

    public function test_the_browser_list_matches_the_server_list(): void
    {
        $javascript = file_get_contents(
            resource_path('js/countries.js')
        );

        $codes = config('countries.dialling_codes');

        foreach ($codes as $iso => $code) {
            $this->assertStringContainsString(
                "{ iso: '{$iso}', code: {$code},",
                $javascript,
                "{$iso} is missing from the browser's list, or is on a different code."
            );
        }

        /*
         * A country with no artwork would show an empty box beside its
         * name, which reads as a fault rather than a country.
         */
        $flags = file_get_contents(
            resource_path('css/flags.css')
        );

        foreach (array_keys($codes) as $iso) {
            $this->assertStringContainsString(
                '.pm-flag-'.strtolower($iso).' {',
                $flags,
                "{$iso} has no flag in the sprite."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Numbers recorded before V1.0.30
    |--------------------------------------------------------------------------
    */

    public function test_a_number_recorded_before_the_split_still_reads(): void
    {
        /*
         * Written straight to the column, the way a row that predates the
         * migration looks: free text, and no country beside it.
         */
        $party = Party::create([
            'type' => 'person',
            'given_names' => 'Ama',
            'surname' => 'Mensah',
            'name' => 'Ama Mensah',
            'email' => 'ama@example.test',
        ]);

        \Illuminate\Support\Facades\DB::table('parties')
            ->where('id', $party->id)
            ->update([
                'phone' => '024 434 7118',
                'phone_country' => null,
            ]);

        $party->refresh();

        $this->assertSame(
            '024 434 7118',
            $party->phone_display,
            'An old number is shown exactly as it was typed, not mangled.'
        );

        $this
            ->getJson("/api/parties/{$party->id}")
            ->assertOk()
            ->assertJsonPath('phone', '024 434 7118')
            ->assertJsonPath('phone_country', null);
    }

    public function test_the_migration_converts_only_what_it_can_read(): void
    {
        /*
         * The migration rewrites a number that already carries a calling
         * code and leaves everything else alone. Both halves of that
         * decision are PhoneNumber's to make, so both are asserted here.
         */
        $normalised = '+'.preg_replace('/[^0-9]/', '', substr('+233 24 434 7118', 1));

        $this->assertSame('+233244347118', $normalised);

        $this->assertSame(
            'GH',
            PhoneNumber::countryFor($normalised)
        );

        $this->assertTrue(
            PhoneNumber::isE164($normalised)
        );

        $this->assertNull(
            PhoneNumber::countryFor('024 434 7118'),
            'Without a calling code there is nothing to read, so it is left alone.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The fields on screen
    |--------------------------------------------------------------------------
    */

    public function test_every_telephone_field_uses_the_country_control(): void
    {
        $views = [
            'app/parties.blade.php',
            'app/properties.blade.php',
            'app/settings.blade.php',
            'app/panels/users.blade.php',
            'app/admin.blade.php',
            'auth/signup.blade.php',
            'layouts/app.blade.php',
        ];

        $fields = 0;

        foreach ($views as $view) {
            $markup = file_get_contents(
                resource_path('views/'.$view)
            );

            $fields += substr_count($markup, '<x-phone-field');

            /*
             * A plain input would store whatever was typed and quietly
             * lose the country, which is the whole defect being fixed.
             */
            $this->assertDoesNotMatchRegularExpression(
                '/<input[^>]*id="[a-z-]*phone"/',
                $markup,
                "{$view} still has a bare telephone input."
            );
        }

        $this->assertSame(
            13,
            $fields,
            'Every telephone field on a server-rendered page uses the control.'
        );
    }

    public function test_every_shared_code_default_belongs_to_that_code(): void
    {
        $codes = config('countries.dialling_codes');

        foreach (config('countries.preferred_for_code') as $code => $iso) {
            $this->assertArrayHasKey($iso, $codes);

            $this->assertSame(
                $code,
                $codes[$iso],
                "{$iso} is the default for +{$code} but is not on it."
            );
        }
    }
}
