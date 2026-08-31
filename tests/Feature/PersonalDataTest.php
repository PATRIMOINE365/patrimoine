<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\User;
use App\Support\OrganisationContext;
use App\Support\PersonalData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.34: answering a person who asks what is held about them, or asks to
 * be forgotten.
 *
 * The hard part is not the export. It is erasure, because Patrimoine is
 * built on records that must not be destroyed — and the answer is to
 * destroy the person rather than the record: every identifying field is
 * overwritten with a permanent reference, and the invoices and journal
 * entries go on pointing at the same row with nobody behind it.
 *
 * These assertions hold that line from both sides. Nothing identifying may
 * survive an erasure, and nothing financial may be lost to one.
 */
class PersonalDataTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::factory()->create();

        $this->administrator = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create([
                    'role' => 'administrator',
                    'password' => bcrypt('correct-horse-battery'),
                ])
        );
    }

    private function makeParty(array $overrides = []): Party
    {
        return OrganisationContext::runAs(
            (int) $this->organisation->id,
            function () use ($overrides): Party {
                $party = Party::create(array_merge([
                    'type' => 'person',
                    'name' => 'Ama Mensah',
                    'phone' => '+233200000700',
                    'phone_country' => 'GH',
                    'email' => 'ama@example.test',
                    'address' => '4 Flamboyant Lane, Accra',
                    'id_number' => 'GHA-123456789',
                    'bank_account_number' => '0123456789',
                    'notes' => 'Pays on the first of the month.',
                ], $overrides));

                PartyRole::create([
                    'party_id' => $party->id,
                    'role' => 'tenant',
                ]);

                return $party;
            }
        );
    }

    /*
    |----------------------------------------------------------------------
    | Everything personal is accounted for
    |----------------------------------------------------------------------
    */

    public function test_erasure_covers_every_personal_column_on_a_party(): void
    {
        /*
         * The point of this test: somebody adds a column to parties for a
         * next-of-kin, or a second address, and forgets that erasure exists.
         * Anything not named here is a field that would survive being
         * forgotten.
         */
        $known = array_merge(
            PersonalData::PARTY_IDENTITY,
            [
                /* Not personal: structural, or already covered elsewhere. */
                'id',
                'organisation_id',
                'type',
                'email_policy',
                'erased_at',
                /*
                 * Archiving is about whether a record is offered in the
                 * lists, not about the person: it holds no detail of
                 * theirs and survives erasure untouched.
                 */
                'archived_at',
                'archived_by_user_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ]
        );

        $columns = Schema::getColumnListing('parties');

        $unaccounted = array_diff($columns, $known);

        $this->assertSame(
            [],
            array_values($unaccounted),
            'These party columns are neither structural nor erased: '
                .implode(', ', $unaccounted)
        );
    }

    /*
    |----------------------------------------------------------------------
    | Producing data
    |----------------------------------------------------------------------
    */

    public function test_anybody_can_download_their_own_data(): void
    {
        foreach (['administrator', 'property_manager', 'viewer'] as $role) {
            $user = OrganisationContext::runAs(
                (int) $this->organisation->id,
                fn (): User => User::factory()
                    ->forOrganisation($this->organisation)
                    ->create(['role' => $role])
            );

            Sanctum::actingAs($user);

            $response = $this->get('/api/auth/me/data');

            $response->assertOk();

            $body = json_decode($response->streamedContent(), true);

            $this->assertSame(
                $user->email,
                $body['account']['email'],
                $role.' should receive their own account data.'
            );
        }
    }

    public function test_a_download_of_my_own_data_never_carries_a_password(): void
    {
        Sanctum::actingAs($this->administrator);

        $content = $this->get('/api/auth/me/data')->streamedContent();

        /*
         * The word appears in the notes — "your password is stored only as a
         * hash" — so what matters is the hash itself, and that no field is
         * named password.
         */
        $this->assertStringNotContainsString(
            $this->administrator->password,
            $content
        );

        $body = json_decode($content, true);

        $this->assertArrayNotHasKey('password', $body['account']);
        $this->assertArrayNotHasKey('remember_token', $body['account']);
    }

    public function test_an_administrator_can_produce_one_partys_data(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $response = $this->get('/api/parties/'.$party->id.'/data');

        $response->assertOk();

        $body = json_decode($response->streamedContent(), true);

        $this->assertSame('Ama Mensah', $body['party']['name']);
        $this->assertSame('ama@example.test', $body['party']['email']);
        $this->assertContains('tenant', $body['roles']);
    }

    public function test_producing_a_partys_data_is_itself_recorded(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $this->get('/api/parties/'.$party->id.'/data')->assertOk();

        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()
                ->where('action', 'party.data_exported')
                ->where('entity_id', (string) $party->id)
                ->exists()
        );
    }

    public function test_a_manager_cannot_produce_a_partys_data(): void
    {
        $party = $this->makeParty();

        $manager = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create(['role' => 'property_manager'])
        );

        Sanctum::actingAs($manager);

        $this->get('/api/parties/'.$party->id.'/data')->assertForbidden();
    }

    public function test_an_administrator_can_download_the_whole_organisation(): void
    {
        $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $response = $this->get('/api/organisation/data');

        $response->assertOk();

        $body = json_decode($response->streamedContent(), true);

        $this->assertArrayHasKey('parties', $body);
        $this->assertArrayHasKey('activity_logs', $body);

        $this->assertNotEmpty($body['parties']);

        foreach ($body['users'] as $user) {
            $this->assertArrayNotHasKey(
                'password',
                $user,
                'A whole-organisation export must not carry password hashes.'
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | Erasing a person
    |----------------------------------------------------------------------
    */

    private function erase(Party $party, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(
            '/api/parties/'.$party->id.'/erase',
            array_merge([
                'name_confirmation' => $party->name,
                'password' => 'correct-horse-battery',
            ], $payload)
        );
    }

    public function test_erasing_a_person_leaves_nothing_identifying(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $this->erase($party)->assertOk();

        $party->refresh();

        $this->assertSame('Erased party #'.$party->id, $party->name);

        $this->assertNotNull($party->erased_at);

        foreach (PersonalData::PARTY_IDENTITY as $field) {
            if ($field === 'name') {
                continue;
            }

            $this->assertNull(
                $party->{$field},
                $field.' should not survive an erasure.'
            );
        }
    }

    public function test_an_erased_person_is_never_emailed_again(): void
    {
        $party = $this->makeParty(['email_policy' => 'always']);

        Sanctum::actingAs($this->administrator);

        $this->erase($party)->assertOk();

        $this->assertSame('never', $party->refresh()->email_policy);
    }

    public function test_the_accounts_survive_the_erasure(): void
    {
        $party = $this->makeParty();

        OrganisationContext::runAs(
            (int) $this->organisation->id,
            function () use ($party): void {
                DB::table('party_roles')->insert([
                    'organisation_id' => $this->organisation->id,
                    'party_id' => $party->id,
                    'role' => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        );

        $rolesBefore = DB::table('party_roles')
            ->where('party_id', $party->id)
            ->count();

        Sanctum::actingAs($this->administrator);

        $this->erase($party)->assertOk();

        $this->assertDatabaseHas('parties', ['id' => $party->id]);

        $this->assertSame(
            $rolesBefore,
            DB::table('party_roles')->where('party_id', $party->id)->count(),
            'Erasing the person must not remove what the accounts depend on.'
        );
    }

    public function test_the_log_records_the_erasure_without_undoing_it(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $this->erase($party)->assertOk();

        $event = ActivityLog::withoutGlobalScopes()
            ->where('action', 'party.erased')
            ->first();

        $this->assertNotNull($event);

        /*
         * The whole point of erasure is that the name is gone. Writing it
         * into an append-only log would put it straight back, permanently.
         */
        $this->assertStringNotContainsString(
            'Ama Mensah',
            json_encode($event->toArray()),
            'The log must not preserve the name that was just erased.'
        );

        $this->assertStringContainsString(
            'Erased party #'.$party->id,
            (string) $event->entity_label
        );
    }

    public function test_a_person_cannot_be_erased_twice(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $this->erase($party)->assertOk();

        $party->refresh();

        $response = $this->postJson('/api/parties/'.$party->id.'/erase', [
            'name_confirmation' => $party->name,
            'password' => 'correct-horse-battery',
        ]);

        $response->assertStatus(422);

        $this->assertSame('PM-1040', $response->json('code'));
    }

    public function test_the_managing_organisation_cannot_be_erased(): void
    {
        $party = $this->makeParty(['name' => 'Akwaba Property Management']);

        OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn () => PartyRole::create([
                'party_id' => $party->id,
                'role' => 'managing_organisation',
            ])
        );

        Sanctum::actingAs($this->administrator);

        $response = $this->erase($party->refresh());

        $response->assertStatus(422);

        $this->assertSame('PM-1041', $response->json('code'));

        $this->assertNull($party->refresh()->erased_at);
    }

    public function test_the_name_must_be_typed_back_exactly(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $response = $this->erase($party, ['name_confirmation' => 'ama mensah']);

        $response->assertStatus(422);

        $this->assertSame('PM-1042', $response->json('code'));

        $this->assertNull($party->refresh()->erased_at);
    }

    public function test_the_password_must_be_re_entered(): void
    {
        $party = $this->makeParty();

        Sanctum::actingAs($this->administrator);

        $this->erase($party, ['password' => 'not-the-password'])
            ->assertStatus(422);

        $this->assertNull($party->refresh()->erased_at);
    }

    public function test_a_manager_cannot_erase_anybody(): void
    {
        $party = $this->makeParty();

        $manager = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create(['role' => 'property_manager'])
        );

        Sanctum::actingAs($manager);

        $this->erase($party)->assertForbidden();

        $this->assertNull($party->refresh()->erased_at);
    }

    public function test_a_stranger_gets_nothing(): void
    {
        $party = $this->makeParty();

        $this->getJson('/api/auth/me/data')->assertUnauthorized();
        $this->getJson('/api/parties/'.$party->id.'/data')->assertUnauthorized();
        $this->getJson('/api/organisation/data')->assertUnauthorized();
        $this->postJson('/api/parties/'.$party->id.'/erase')->assertUnauthorized();
    }
}
