<?php

namespace Tests\Feature;

use App\Mail\CustomerAccountClosedMail;
use App\Models\ActivityLog;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.32: an organisation can close its own account.
 *
 * The console could always destroy a customer; this is the same
 * destruction asked for by the people whose data it is, from the Settings
 * page. It is the only route in the application that removes an
 * organisation, so what guards it matters more than what it does.
 *
 * Three things are asserted here above all: that it really does destroy
 * everything, that it destroys nobody else's, and that it cannot be
 * reached by accident.
 */
class AccountClosureTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->organisation = Organisation::factory()->create([
            'name' => 'Closing Properties Ltd',
        ]);

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

    /**
     * A party belonging to the organisation under test.
     */
    private function giveThemSomethingToLose(): Party
    {
        return OrganisationContext::runAs(
            (int) $this->organisation->id,
            function (): Party {
                $party = Party::create([
                    'type' => 'person',
                    'name' => 'Ama Tenant',
                    'phone' => '+233200000900',
                    'phone_country' => 'GH',
                    'email' => 'ama@example.test',
                ]);

                PartyRole::create([
                    'party_id' => $party->id,
                    'role' => 'tenant',
                ]);

                return $party;
            }
        );
    }

    private function close(array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->deleteJson(
            '/api/organisation',
            array_merge(
                [
                    'name_confirmation' => 'Closing Properties Ltd',
                    'password' => 'correct-horse-battery',
                ],
                $payload
            )
        );
    }

    /*
    |----------------------------------------------------------------------
    | It works
    |----------------------------------------------------------------------
    */

    public function test_an_administrator_can_close_their_own_account(): void
    {
        $party = $this->giveThemSomethingToLose();

        Sanctum::actingAs($this->administrator);

        $this->close()->assertOk();

        $this->assertDatabaseMissing('organisations', [
            'id' => $this->organisation->id,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->administrator->id,
        ]);

        $this->assertDatabaseMissing('parties', [
            'id' => $party->id,
        ]);
    }

    public function test_kality_is_told_that_a_customer_left(): void
    {
        Sanctum::actingAs($this->administrator);

        $this->close()->assertOk();

        Mail::assertQueued(
            CustomerAccountClosedMail::class,
            fn (CustomerAccountClosedMail $mail): bool =>
                $mail->organisationName === 'Closing Properties Ltd'
        );
    }

    public function test_the_closure_is_recorded_where_it_can_survive(): void
    {
        $platform = Organisation::factory()->create([
            'name' => 'Kality Ltd',
            'is_platform' => true,
        ]);

        Sanctum::actingAs($this->administrator);

        $this->close()->assertOk();

        /*
         * The customer's own log died with the rest of their data, which is
         * what deletion means. The record lives on the platform side.
         */
        $recorded = ActivityLog::withoutGlobalScopes()
            ->where('action', 'organisation.closed_by_customer')
            ->first();

        $this->assertNotNull($recorded);

        $this->assertSame(
            (int) $platform->id,
            (int) $recorded->organisation_id
        );

        $this->assertSame(
            'Closing Properties Ltd',
            $recorded->entity_label
        );
    }

    public function test_nobody_else_loses_anything(): void
    {
        $bystander = Organisation::factory()->create([
            'name' => 'Innocent Bystander Ltd',
        ]);

        $theirUser = OrganisationContext::runAs(
            (int) $bystander->id,
            fn (): User => User::factory()
                ->forOrganisation($bystander)
                ->create(['role' => 'administrator'])
        );

        $theirParty = OrganisationContext::runAs(
            (int) $bystander->id,
            fn (): Party => Party::create([
                'type' => 'person',
                'name' => 'Kofi Owner',
                'phone' => '+233200000901',
                'phone_country' => 'GH',
                'email' => 'kofi@example.test',
            ])
        );

        Sanctum::actingAs($this->administrator);

        $this->close()->assertOk();

        $this->assertDatabaseHas('organisations', ['id' => $bystander->id]);
        $this->assertDatabaseHas('users', ['id' => $theirUser->id]);
        $this->assertDatabaseHas('parties', ['id' => $theirParty->id]);
    }

    /*
    |----------------------------------------------------------------------
    | It cannot be reached by accident
    |----------------------------------------------------------------------
    */

    public function test_the_organisation_name_must_be_typed_back_exactly(): void
    {
        Sanctum::actingAs($this->administrator);

        $response = $this->close([
            'name_confirmation' => 'closing properties ltd',
        ]);

        $response->assertStatus(422);

        $this->assertSame(
            'PM-1038',
            $response->json('code'),
            'The refusal should carry the code that explains it.'
        );

        $this->assertDatabaseHas('organisations', [
            'id' => $this->organisation->id,
        ]);
    }

    public function test_the_password_must_be_re_entered_correctly(): void
    {
        Sanctum::actingAs($this->administrator);

        $this->close(['password' => 'not-the-password'])
            ->assertStatus(422);

        $this->assertDatabaseHas('organisations', [
            'id' => $this->organisation->id,
        ]);
    }

    public function test_both_answers_are_required(): void
    {
        Sanctum::actingAs($this->administrator);

        $this->deleteJson('/api/organisation', [])
            ->assertStatus(422);

        $this->assertDatabaseHas('organisations', [
            'id' => $this->organisation->id,
        ]);
    }

    public function test_a_manager_cannot_close_the_account(): void
    {
        $manager = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create([
                    'role' => 'property_manager',
                    'password' => bcrypt('correct-horse-battery'),
                ])
        );

        Sanctum::actingAs($manager);

        $this->close()->assertForbidden();

        $this->assertDatabaseHas('organisations', [
            'id' => $this->organisation->id,
        ]);
    }

    public function test_signing_out_is_not_optional_for_a_stranger(): void
    {
        $this->close()->assertUnauthorized();

        $this->assertDatabaseHas('organisations', [
            'id' => $this->organisation->id,
        ]);
    }

    public function test_the_platform_organisation_cannot_be_closed_from_here(): void
    {
        $platform = Organisation::factory()->create([
            'name' => 'Kality Ltd',
            'is_platform' => true,
        ]);

        $staff = OrganisationContext::runAs(
            (int) $platform->id,
            fn (): User => User::factory()
                ->forOrganisation($platform)
                ->create([
                    'email' => 'komla@patrimoine365.com',
                    'role' => 'administrator',
                    'password' => bcrypt('correct-horse-battery'),
                ])
        );

        Sanctum::actingAs($staff);

        $response = $this->deleteJson('/api/organisation', [
            'name_confirmation' => 'Kality Ltd',
            'password' => 'correct-horse-battery',
        ]);

        $response->assertStatus(422);

        $this->assertSame('PM-1039', $response->json('code'));

        $this->assertDatabaseHas('organisations', ['id' => $platform->id]);
    }

    /*
    |----------------------------------------------------------------------
    | Everything it owns really does mean everything
    |----------------------------------------------------------------------
    */

    public function test_the_deletion_order_covers_every_tenanted_table(): void
    {
        $order = new \ReflectionClassConstant(
            \App\Services\PlatformOrganisationDeletionService::class,
            'DELETION_ORDER'
        );

        $covered = $order->getValue();

        /*
         * Tables added after the service was written are the ones that get
         * forgotten. lease_wizard_drafts arrived in V1.0.31 and was one.
         */
        foreach ([
            'lease_wizard_drafts',
            'activity_logs',
            'journal_lines',
            'journal_entries',
            'invoices',
            'payments',
            'leases',
            'units',
            'buildings',
            'parties',
        ] as $table) {
            $this->assertContains(
                $table,
                $covered,
                $table.' is organisation-owned and must be destroyed with it.'
            );
        }
    }
}
